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

namespace IQ2i\StoriaBundle\View\Builder;

use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Twig\Environment;

class ComponentBuilder extends AbstractBuilder
{
    public function __construct(
        string $defaultPath,
        Environment $twig,
        private readonly ComponentTemplateFinder $componentTemplateFinder,
        private readonly ComponentFactory $componentFactory,
    ) {
        parent::__construct($defaultPath, $twig);
    }

    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('component', $config);
    }

    public function build(Request $request, string $path, string $name, array $config): ?View
    {
        $template = $config['component'];

        $variantPath = $request->query->get('variant');
        if (isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $skeletonPath = __DIR__.'/../../../skeleton/component.tpl.php';

            $componentProperties = array_values($this->getComponentProperties($template));

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
                'template' => str_replace('/', ':', (string) $template),
                'args' => $variantArgs,
                'blocks' => $variantConfig['blocks'],
            ];

            if (isset($parameters['blocks']['content'])) {
                $parameters['content'] = $parameters['blocks']['content'];
                unset($parameters['blocks']['content']);
            }

            $includeContent = $this->generateInclude($skeletonPath, $parameters);
            $twigContent = $this->getTwigContent($template);
            $htmlContent = $this->generateHtml($includeContent, $parameters['args']);

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
            true,
            false,
            $twigContent ?? null,
            $htmlContent ?? null,
            $includeContent ?? null,
            $markdownContent ?? null,
            $variants
        );
    }

    protected function getTwigContent(string $template): string
    {
        $template = $this->componentTemplateFinder->findAnonymousComponentTemplate($template);

        return parent::getTwigContent($template);
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
