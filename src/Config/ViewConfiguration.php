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
use Symfony\UX\TwigComponent\ComponentTemplateFinder;

class ViewConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('component');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('name')->end()
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
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => isset($v['template']) && isset($v['component']))
                ->thenInvalid('"template" and "component" cannot be used together.')
            ->end()
            ->validate()
                ->ifTrue(static fn ($v) => isset($v['component']) && !class_exists(ComponentTemplateFinder::class))
                ->thenInvalid('TwigComponent support cannot be enabled as the Symfony UX TwigComponent package is not installed. Try running "composer require symfony/ux-twig-component".')
            ->end()
        ;

        return $treeBuilder;
    }
}
