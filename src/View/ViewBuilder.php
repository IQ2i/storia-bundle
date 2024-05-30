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
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Twig\Environment;

readonly class ViewBuilder
{
    public function __construct(
        private string $defaultPath,
        private Environment $twig,
        private ComponentTemplateFinder $componentTemplateFinder,
        private ComponentFactory $componentFactory,
    ) {
    }

    public function createFromRequest(Request $request): ?View
    {
        $path = $request->attributes->get('view');
        if (null === $path || !file_exists($this->defaultPath.'/'.$path.'.yaml')) {
            return null;
        }

        $config = (new Processor())->processConfiguration(
            new ViewConfiguration(),
            [Yaml::parse(file_get_contents($this->defaultPath.'/'.$path.'.yaml'))]
        );

        $name = $config['name'] ?? null;
        if (null === $name) {
            $name = pathinfo(str_replace('.yaml', '', (string) $path), \PATHINFO_FILENAME);
            $name = ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name))));
        }

        $isPage = false;
        $template = $config['template'] ?? null;
        if (null === $template && @file_exists($this->defaultPath.'/'.$path.'.html.twig')) {
            $isPage = true;
            $template = $this->defaultPath.'/'.$path.'.html.twig';
        }

        $isComponent = false;
        if (null === $template && null !== $config['component']) {
            $isComponent = true;
            $template = $config['component'];
        }

        if (null === $template) {
            throw new \LogicException(sprintf('Missing template for component "%s"', $path));
        }

        $variantPath = $request->query->get('variant');
        if (isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $skeletonPath = $isComponent
                ? __DIR__.'/../../skeleton/component.tpl.php'
                : __DIR__.'/../../skeleton/template.tpl.php';

            $componentProperties = [];
            if ($isComponent) {
                $componentProperties = array_values($this->getComponentProperties($template));
            }

            $variantArgs = [];
            foreach ($variantConfig['args'] as $argName => $argValue) {
                if (\in_array($argName, $componentProperties)) {
                    continue;
                }

                if (\is_array($argValue)) {
                    $variantArgs[':'.$argName] = str_replace('"', "'", json_encode($argValue, \JSON_FORCE_OBJECT | \JSON_NUMERIC_CHECK));
                } else {
                    $variantArgs[$argName] = $argValue;
                }
            }

            $parameters = [
                'template' => $template,
                'args' => $variantArgs,
                'blocks' => $variantConfig['blocks'],
            ];

            if ($isComponent && isset($parameters['blocks']['content'])) {
                $parameters['content'] = $parameters['blocks']['content'];
                unset($parameters['blocks']['content']);
            }

            $includeContent = null;
            if (!$isPage) {
                $includeContent = $this->generateInclude($skeletonPath, $parameters);
            }

            $twigContent = $this->getTwigContent($template, $isComponent);
            $htmlContent = $this->generateHtml($isPage ? $twigContent : $includeContent, $parameters['args']);

            $markdownContent = null;
            if (file_exists($this->defaultPath.'/'.$path.'.md')) {
                $markdownContent = MarkdownExtra::defaultTransform(@file_get_contents($this->defaultPath.'/'.$path.'.md'));
            }
        }

        $variants = array_map(static fn (string $name): Variant => new Variant(
            $name,
            ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name)))),
            $variantPath === $name
        ), array_keys($config['variants']));

        return new View(
            $path,
            $name,
            $template,
            $isComponent,
            $isPage,
            $twigContent ?? null,
            $htmlContent ?? null,
            $includeContent ?? null,
            $markdownContent ?? null,
            $variants
        );
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

    private function generateHtml(string $content, array $parameters): string
    {
        $template = $this->twig->createTemplate($content);

        return $template->render($parameters);
    }

    private function getComponentProperties(string $template): array
    {
        $metadata = $this->componentFactory->metadataFor($template);
        if (!$metadata->get('class')) {
            return [];
        }

        $properties = [];
        $reflectionClass = new \ReflectionClass($metadata->getClass());
        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();

            if ($metadata->isPublicPropsExposed() && $property->isPublic()) {
                $type = $property->getType();
                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type;

                $value = $property->getDefaultValue();
                $propertyDisplay = $typeName.' $'.$propertyName.(null !== $value ? ' = '.json_encode($value) : '');
                $properties[$property->name] = $propertyDisplay;
            }

            foreach ($property->getAttributes(ExposeInTemplate::class) as $exposeAttribute) {
                /** @var ExposeInTemplate $attribute */
                $attribute = $exposeAttribute->newInstance();
                $properties[$property->name] = $attribute->name ?? $property->name;
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            foreach ($method->getAttributes(ExposeInTemplate::class) as $exposeAttribute) {
                /** @var ExposeInTemplate $attribute */
                $attribute = $exposeAttribute->newInstance();
                $properties[$method->getName()] = $attribute->name ?? lcfirst(str_replace('get', '', $method->getName()));
            }
        }

        return $properties;
    }
}
