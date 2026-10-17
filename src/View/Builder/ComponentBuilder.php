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
use IQ2i\StoriaBundle\Util\TwigLiteral;
use IQ2i\StoriaBundle\View\Dto\View;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;
use Twig\Environment;

/**
 * @internal
 */
class ComponentBuilder extends AbstractBuilder
{
    /** Names are written in the Twig source: only plain identifiers are accepted. */
    private const string ATTRIBUTE_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_-]*$/';

    private const string BLOCK_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

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
        $twigContent = null;
        $htmlContent = null;
        $includeContent = null;
        $markdownContent = null;

        if (null !== $variantPath && isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

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
            $variantArgs = array_diff_key($resolvedArgs, array_flip($exposedNames));

            foreach (array_keys($variantArgs) as $argName) {
                if (1 !== preg_match(self::ATTRIBUTE_NAME_PATTERN, (string) $argName)) {
                    throw new \LogicException(\sprintf('Argument name "%s" of component "%s" is not a valid attribute name.', $argName, $path));
                }
            }

            foreach (array_keys($resolvedBlocks) as $blockName) {
                if (1 !== preg_match(self::BLOCK_NAME_PATTERN, (string) $blockName)) {
                    throw new \LogicException(\sprintf('Block name "%s" of component "%s" is not a valid block name.', $blockName, $path));
                }
            }

            $skeletonPath = __DIR__.'/../../../skeleton/component.tpl.php';
            $parameters = ['template' => str_replace('/', ':', (string) $template)];

            // What the Include tab shows: the values written as Twig literals, never compiled
            $includeContent = $this->generateInclude($skeletonPath, $parameters + $this->splitContent(
                $this->attributesForInclude(array_replace($variantArgs, $appliedOverrides)),
                $resolvedBlocks
            ));
            $twigContent = $this->getTwigContent($template);

            // What is rendered: values and blocks stay in the rendering context, they never reach the Twig source
            $renderArgs = array_replace($variantArgs, $renderOverrides);
            $attributes = [];
            foreach (array_keys($renderArgs) as $index => $argName) {
                $attributes[':'.$argName] = \sprintf('__storia_args[%d]', $index);
            }

            $blockExpressions = [];
            foreach (array_keys($resolvedBlocks) as $blockName) {
                $blockExpressions[$blockName] = \sprintf("{{ __storia_blocks['%s']|raw }}", $blockName);
            }

            $htmlContent = $this->generateHtml(
                $this->generateInclude($skeletonPath, $parameters + $this->splitContent($attributes, $blockExpressions)),
                ['__storia_args' => array_values($renderArgs), '__storia_blocks' => $resolvedBlocks]
            );

            $markdownContent = $this->loadMarkdown($path);
        }

        $variants = $this->buildVariants($config['variants'], $variantPath);

        return new View(
            $path,
            $twigContent,
            $htmlContent,
            $includeContent,
            $markdownContent,
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
     * The default block is passed to the skeleton apart from the others.
     *
     * @param array<string, string> $args
     * @param array<string, string> $blocks
     *
     * @return array{args: array<string, string>, blocks: array<string, string>, content?: string}
     */
    private function splitContent(array $args, array $blocks): array
    {
        $parameters = ['args' => $args, 'blocks' => $blocks];

        if (isset($blocks['content'])) {
            $parameters['content'] = $blocks['content'];
            unset($parameters['blocks']['content']);
        }

        return $parameters;
    }

    /**
     * Strings are written as plain attributes, any other value as a Twig literal.
     *
     * @param array<string, mixed> $args
     *
     * @return array<string, string>
     */
    private function attributesForInclude(array $args): array
    {
        $attributes = [];
        foreach ($args as $name => $value) {
            if (\is_string($value)) {
                $attributes[$name] = htmlspecialchars($value, \ENT_QUOTES);
            } else {
                $attributes[':'.$name] = TwigLiteral::export($value);
            }
        }

        return $attributes;
    }
}
