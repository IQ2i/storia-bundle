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

namespace IQ2i\StoriaBundle\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Form;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;

class ViewConfiguration implements ConfigurationInterface
{
    public const array TAGS = ['data', 'repeat', 'lorem'];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('component');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('template')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('component')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('variants')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->arrayNode('args')
                                ->variablePrototype()->end()
                            ->end()
                            ->arrayNode('blocks')
                                ->variablePrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('form')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('options')
                    ->variablePrototype()->end()
                ->end()
                ->scalarNode('form_theme')->end()
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => \count(array_filter([$v['template'] ?? null, $v['component'] ?? null, $v['form'] ?? null])) > 1)
                ->thenInvalid('"template", "component" and "form" cannot be used together.')
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => isset($v['component']) && !class_exists(ComponentTemplateFinder::class))
                ->thenInvalid('TwigComponent support cannot be enabled as the Symfony UX TwigComponent package is not installed. Try running "composer require symfony/ux-twig-component".')
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => isset($v['form']) && !class_exists(Form::class))
                ->thenInvalid('Form support cannot be enabled as the Symfony Form package is not installed. Try running "composer require symfony/form".')
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => isset($v['form']) && !empty($v['variants']))
                ->thenInvalid('You can not define variants for the form view.')
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => (isset($v['template']) || isset($v['component'])) && !empty($v['options']))
                ->thenInvalid('Options is allowed for the form view only.')
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => (isset($v['template']) || isset($v['component'])) && !empty($v['form_theme']))
                ->thenInvalid('"form_theme" is allowed for the form view only.')
            ->end()
            ->validate()
                ->always(function (array $v): array {
                    if (null !== $tag = $this->findUnknownTag($v)) {
                        throw new InvalidConfigurationException(\sprintf('Unknown tag "!%s": supported tags are %s.', $tag, implode(', ', array_map(static fn (string $t): string => '!'.$t, self::TAGS))));
                    }

                    return $v;
                })
            ->end()
        ;

        return $treeBuilder;
    }

    private function findUnknownTag(mixed $value): ?string
    {
        if ($value instanceof TaggedValue) {
            return \in_array($value->getTag(), self::TAGS, true) ? $this->findUnknownTag($value->getValue()) : $value->getTag();
        }

        if (\is_array($value)) {
            foreach ($value as $item) {
                if (null !== $tag = $this->findUnknownTag($item)) {
                    return $tag;
                }
            }
        }

        return null;
    }
}
