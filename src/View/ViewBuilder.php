<?php

/*
 * This file is part of the UI Storia project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\StoriaBundle\View;

use IQ2i\StoriaBundle\Config\ViewConfiguration;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Michelf\MarkdownExtra;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Twig\Environment;

readonly class ViewBuilder
{
    public function __construct(
        private string $defaultPath,
        private Environment $twig,
        private ComponentTemplateFinder $componentTemplateFinder,
    ) {
    }

    public function createFromRequest(Request $request): ?View
    {
        $viewPath = $request->attributes->get('view');
        if (null === $viewPath) {
            return null;
        }

        $yaml = Yaml::parse(file_get_contents($this->defaultPath.'/'.$viewPath.'.yaml'));
        $viewConfiguration = new ViewConfiguration();
        $config = (new Processor())->processConfiguration($viewConfiguration, [$yaml]);

        $viewName = $config['name'] ?? null;
        if (null === $viewName) {
            $viewName = pathinfo(str_replace('.yaml', '', (string) $viewPath), \PATHINFO_FILENAME);
            $viewName = ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $viewName))));
        }

        $isComponent = false;
        $isLocal = false;
        $viewTemplate = $config['template'] ?? null;
        if (null === $viewTemplate && @file_exists($this->defaultPath.'/'.$viewPath.'.html.twig')) {
            $isLocal = true;
            $viewTemplate = $this->defaultPath.'/'.$viewPath.'.html.twig';
        }

        if (null === $viewTemplate && null !== $config['component']) {
            $isComponent = true;
            $viewTemplate = $config['component'];
        }

        if (null === $viewTemplate) {
            throw new \LogicException(sprintf('Missing template for component "%s"', $viewPath));
        }

        $view = new View($viewPath, $viewName, $viewTemplate, $request->query->get('variant'));

        $markdownPath = $view->getPath().'.md';
        $markdownContent = @file_get_contents($this->defaultPath.'/'.$markdownPath);
        if (false !== $markdownContent) {
            $markdownContent = MarkdownExtra::defaultTransform($markdownContent);
        }

        foreach ($config['variants'] as $variantPath => $variantConfig) {
            $variantName = $variantConfig['name'] ?? null;
            if (null === $variantName) {
                $variantName = ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], (string) $variantPath))));
            }

            $variant = new Variant($variantPath, $variantName);

            $skeletonPath = $isComponent
                ? __DIR__.'/../../skeleton/component.tpl.php'
                : __DIR__.'/../../skeleton/template.tpl.php';

            $variantArgs = [];
            foreach ($variantConfig['args'] as $name => $value) {
                if (\is_array($value)) {
                    $variantArgs[':'.$name] = str_replace('"', "'", json_encode($value, \JSON_FORCE_OBJECT | \JSON_NUMERIC_CHECK));
                } else {
                    $variantArgs[$name] = $value;
                }
            }

            $parameters = [
                'template' => $view->getTemplate(),
                'args' => $variantArgs,
                'blocks' => $variantConfig['blocks'],
            ];

            if ($isComponent && isset($parameters['blocks']['content'])) {
                $parameters['content'] = $parameters['blocks']['content'];
                unset($parameters['blocks']['content']);
            }

            if (!$isLocal) {
                $variant->setIncludeContent($this->generateInclude($skeletonPath, $parameters));
            }

            $variant->setTwigContent($this->getTwigContent($view->getTemplate(), $isComponent));
            $variant->setHtmlContent($this->generateHtml($isLocal ? $variant->getTwigContent() : $variant->getIncludeContent()));
            $variant->setMarkdownContent($markdownContent ?: null);

            $view->addVariant($variant);
        }

        return $view;
    }

    private function generateInclude(string $skeletonPath, array $parameters): string
    {
        ob_start();
        extract($parameters, \EXTR_SKIP);
        include $skeletonPath;

        return ob_get_clean();
    }

    private function getTwigContent(string $template, bool $isComponent = false): string
    {
        if ($isComponent) {
            $template = $this->componentTemplateFinder->findAnonymousComponentTemplate($template);
        }

        if (str_starts_with((string) $template, '/') && @file_exists($template)) {
            return @file_get_contents($template);
        }

        $source = $this->twig->getLoader()->getSourceContext($template);

        return $source->getCode();
    }

    private function generateHtml(string $content): string
    {
        $template = $this->twig->createTemplate($content);

        return $template->render();
    }
}
