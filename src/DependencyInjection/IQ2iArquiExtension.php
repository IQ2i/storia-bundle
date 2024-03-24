<?php

/*
 * This file is part of the Arqui project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\ArquiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class IQ2iArquiExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this, $configs);
        $container->setParameter('arqui_bundle.default_path', $config['default_path']);
        $container->setParameter('arqui_bundle.enabled', $config['enabled']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('iq2i_arqui');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('default_path')
                    ->defaultValue('%kernel.project_dir%/stories')
                ->end()
                ->booleanNode('enabled')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
