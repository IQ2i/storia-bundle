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

use IQ2i\StoriaBundle\Controls\ComponentIntrospector;
use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\Util\Label;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Michelf\MarkdownExtra;
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
        private readonly ComponentIntrospector $introspector,
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
            $metadata = $this->componentFactory->metadataFor($template);

            $resolvedArgs = $this->dataResolver->resolve($variantConfig['args']);
            $resolvedBlocks = $this->dataResolver->resolve($variantConfig['blocks']);
            foreach ($resolvedBlocks as $blockName => $block) {
                if (!\is_string($block)) {
                    throw new \LogicException(\sprintf('Block "%s" of component "%s" must resolve to a string, got %s.', $blockName, $path, get_debug_type($block)));
                }
            }

            ['controls' => $controls, 'overrides' => $renderOverrides, 'display' => $appliedOverrides] = $this->controlsBuilder->applyOverrides(
                $this->controlsBuilder->forComponent($metadata, $resolvedArgs),
                $overrides
            );

            // A value exposed by the class is computed, it cannot be passed to the component
            $exposedNames = $this->introspector->exposedNames($metadata);

            $variantArgs = [];
            foreach ($resolvedArgs as $argName => $argValue) {
                if (\in_array($argName, $exposedNames, true)) {
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

            $includeContent = $this->generateInclude($skeletonPath, [...$parameters, 'args' => $this->argsForInclude($variantArgs, $appliedOverrides)]);
            $twigContent = $this->getTwigContent($template);

            $htmlContent = $this->generateHtml(
                $this->generateInclude($skeletonPath, [...$parameters, 'args' => $this->argsForRendering($variantArgs, $renderOverrides)]),
                [...$variantArgs, '__storia_overrides' => $renderOverrides]
            );

            $markdownContent = null;
            if (file_exists($this->defaultPath.'/'.$path.'.md')) {
                $markdownContent = MarkdownExtra::defaultTransform(@file_get_contents($this->defaultPath.'/'.$path.'.md'));
            }
        }

        $variants = array_map(static fn (string $name): Variant => new Variant(
            $name,
            $config['variants'][$name]['name'] ?? Label::humanize($name),
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
     * The overrides as the user would write them in the Include tab: literal attributes.
     *
     * @param array<string, mixed>        $args
     * @param array<string, scalar|array> $overrides
     *
     * @return array<string, mixed>
     */
    private function argsForInclude(array $args, array $overrides): array
    {
        foreach ($overrides as $name => $value) {
            unset($args[$name], $args[':'.$name]);

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

    /**
     * The overrides never reach the Twig source: the include reads them from the rendering context.
     *
     * @param array<string, mixed> $args
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function argsForRendering(array $args, array $overrides): array
    {
        foreach (array_keys($overrides) as $name) {
            unset($args[$name], $args[':'.$name]);
            $args[':'.$name] = \sprintf("__storia_overrides['%s']", $name);
        }

        return $args;
    }
}
