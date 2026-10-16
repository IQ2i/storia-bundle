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

use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Michelf\MarkdownExtra;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;
use Twig\Environment;

class ComponentBuilder extends AbstractBuilder
{
    public function __construct(
        string $defaultPath,
        Environment $twig,
        private readonly ComponentTemplateFinderInterface $componentTemplateFinder,
        private readonly ComponentFactory $componentFactory,
        private readonly DataResolver $dataResolver,
        private readonly ControlsBuilder $controlsBuilder,
    ) {
        parent::__construct($defaultPath, $twig);
    }

    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('component', $config);
    }

    public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View
    {
        $template = $config['component'];

        $controls = [];
        $appliedOverrides = [];

        if (null !== $variantPath && isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $skeletonPath = __DIR__.'/../../../skeleton/component.tpl.php';

            $componentProperties = array_values($this->getComponentProperties($template));

            $resolvedArgs = $this->dataResolver->resolve($variantConfig['args']);
            $resolvedBlocks = $this->dataResolver->resolve($variantConfig['blocks']);
            foreach ($resolvedBlocks as $blockName => $block) {
                if (!\is_string($block)) {
                    throw new \LogicException(\sprintf('Block "%s" of component "%s" must resolve to a string, got %s.', $blockName, $path, get_debug_type($block)));
                }
            }

            ['controls' => $controls, 'overrides' => $appliedOverrides] = $this->controlsBuilder->applyOverrides(
                $this->controlsBuilder->forComponent($this->componentFactory->metadataFor($template), $resolvedArgs),
                $overrides
            );

            $variantArgs = [];
            foreach ($resolvedArgs as $argName => $argValue) {
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
                'blocks' => $resolvedBlocks,
            ];

            if (isset($parameters['blocks']['content'])) {
                $parameters['content'] = $parameters['blocks']['content'];
                unset($parameters['blocks']['content']);
            }

            $includeContent = $this->generateInclude($skeletonPath, [...$parameters, 'args' => $this->replaceArgs($variantArgs, $appliedOverrides, false)]);
            $twigContent = $this->getTwigContent($template);

            // Overrides never reach the Twig source: they are read from the rendering context
            $renderArgs = $this->replaceArgs($variantArgs, $appliedOverrides, true);
            $htmlContent = $this->generateHtml(
                $this->generateInclude($skeletonPath, [...$parameters, 'args' => $renderArgs]),
                [...$parameters['args'], '__storia_overrides' => $appliedOverrides]
            );

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
            $twigContent ?? null,
            $htmlContent ?? null,
            $includeContent ?? null,
            $markdownContent ?? null,
            $variants,
            $controls,
            $appliedOverrides
        );
    }

    protected function getTwigContent(string $template): string
    {
        $template = $this->componentTemplateFinder->findAnonymousComponentTemplate($template);

        return parent::getTwigContent($template);
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function replaceArgs(array $args, array $overrides, bool $forRendering): array
    {
        foreach ($overrides as $name => $value) {
            unset($args[$name], $args[':'.$name]);

            if ($forRendering) {
                $args[':'.$name] = \sprintf("__storia_overrides['%s']", $name);
                continue;
            }

            $value = $value instanceof \BackedEnum ? $value->value : $value;
            if (\is_array($value)) {
                $args[':'.$name] = str_replace('"', "'", json_encode($value, \JSON_FORCE_OBJECT | \JSON_NUMERIC_CHECK));
            } elseif (\is_bool($value)) {
                $args[':'.$name] = $value ? 'true' : 'false';
            } elseif (\is_int($value) || \is_float($value)) {
                $args[':'.$name] = (string) $value;
            } else {
                $args[$name] = htmlspecialchars((string) $value, \ENT_QUOTES);
            }
        }

        return $args;
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
