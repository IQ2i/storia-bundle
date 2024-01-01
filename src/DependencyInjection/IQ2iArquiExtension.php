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

use IQ2i\ArquiBundle\Controller\IframeController;
use IQ2i\ArquiBundle\Controller\StoryController;
use IQ2i\ArquiBundle\DataCollector\ArquiDataCollector;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Twig\Loader\FilesystemLoader;

final class IQ2iArquiExtension extends Extension implements ConfigurationInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this, $configs);

        $container
            ->register('iq2i_arqui.controller.story', StoryController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                $config['default_path'],
                new Reference('twig'),
                new Reference('router'),
            ])
        ;

        $container
            ->register('iq2i_arqui.controller.iframe', IframeController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                new Reference('twig'),
            ])
        ;

        $container
            ->register('iq2i_arqui.data_collector', ArquiDataCollector::class)
            ->addTag('data_collector', [
                'template' => '@IQ2iArqui/data_collector/template.html.twig',
                'id' => 'iq2i_arqui',
            ])
        ;

        $container
            ->register('iq2i_arqui.twig.loader', FilesystemLoader::class)
            ->addTag('twig.loader')
            ->setArguments([
                $config['default_path'],
            ])
        ;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('iq2i_arqui');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('default_path')
                    ->defaultValue('%kernel.project_dir%/stories')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
