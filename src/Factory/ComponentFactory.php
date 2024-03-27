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

namespace IQ2i\StoriaBundle\Factory;

use IQ2i\StoriaBundle\Config\ComponentConfiguration;
use IQ2i\StoriaBundle\Dto\Component;
use IQ2i\StoriaBundle\Dto\Variant;
use Michelf\MarkdownExtra;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Twig\Environment;

readonly class ComponentFactory
{
    public function __construct(
        private string $defaultPath,
        private Environment $twig,
        private ComponentTemplateFinder $componentTemplateFinder,
    ) {
    }

    public function createFromRequest(Request $request): ?Component
    {
        $componentPath = $request->attributes->get('component');
        if (null === $componentPath) {
            return null;
        }

        $yaml = Yaml::parse(file_get_contents($this->defaultPath.'/'.$componentPath.'.yaml'));
        $componentConfiguration = new ComponentConfiguration();
        $config = (new Processor())->processConfiguration($componentConfiguration, [$yaml]);

        $componentName = $config['name'] ?? null;
        if (null === $componentName) {
            $componentName = pathinfo(str_replace('.yaml', '', (string) $componentPath), \PATHINFO_FILENAME);
            $componentName = ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $componentName))));
        }

        $isComponent = false;
        $isLocal = false;
        $componentTemplate = $config['template'] ?? null;
        if (null === $componentTemplate && @file_exists($this->defaultPath.'/'.$componentPath.'.html.twig')) {
            $isLocal = true;
            $componentTemplate = $this->defaultPath.'/'.$componentPath.'.html.twig';
        }

        if (null === $componentTemplate && null !== $config['component']) {
            $isComponent = true;
            $componentTemplate = $config['component'];
        }

        if (null === $componentTemplate) {
            throw new \LogicException(sprintf('Missing template for component "%s"', $componentPath));
        }

        $component = new Component($componentPath, $componentName, $componentTemplate, $request->query->get('variant'));

        $markdownPath = $component->getPath().'.md';
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
                'template' => $component->getTemplate(),
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

            $variant->setTwigContent($this->getTwigContent($component->getTemplate(), $isComponent));
            $variant->setHtmlContent($this->generateHtml($isLocal ? $variant->getTwigContent() : $variant->getIncludeContent()));
            $variant->setMarkdownContent($markdownContent ?: null);

            $component->addVariant($variant);
        }

        return $component;
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
