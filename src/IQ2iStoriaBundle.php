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

namespace IQ2i\StoriaBundle;

use IQ2i\StoriaBundle\Controller\IframeController;
use IQ2i\StoriaBundle\Controller\ViewController;
use IQ2i\StoriaBundle\DependencyInjection\Compiler\ProfilerPass;
use IQ2i\StoriaBundle\Menu\MenuBuilder;
use IQ2i\StoriaBundle\Twig\HighlightExtension;
use IQ2i\StoriaBundle\Twig\MenuExtension;
use IQ2i\StoriaBundle\Twig\ViewExtension;
use IQ2i\StoriaBundle\View\ViewBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class IQ2iStoriaBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
                ->scalarNode('default_path')
                    ->defaultValue('%kernel.project_dir%/storia')
                ->end()
                ->booleanNode('enabled')->defaultTrue()->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('iq2i_storia.default_path', $config['default_path']);
        $builder->setParameter('iq2i_storia.enabled', $config['enabled']);

        $builder->register(IframeController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                new Reference(ViewBuilder::class),
                new Reference('twig'),
            ]);
        $builder->register(ViewController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                new Reference(ViewBuilder::class),
                new Reference('twig'),
                new Reference('router'),
            ]);

        $builder->register(MenuBuilder::class)
            ->setArguments([
                '%iq2i_storia.default_path%',
                new Reference('router'),
            ]);

        $builder->register(ViewBuilder::class)
            ->setArguments([
                '%iq2i_storia.default_path%',
                new Reference('twig'),
                new Reference('ux.twig_component.component_template_finder'),
                new Reference('ux.twig_component.component_factory'),
            ]);

        $builder->register(HighlightExtension::class)
            ->addTag('twig.extension');
        $builder->register(MenuExtension::class)
            ->addTag('twig.extension')
            ->setArguments([
                new Reference(MenuBuilder::class),
            ]);
        $builder->register(ViewExtension::class)
            ->addTag('twig.extension')
            ->setArguments([
                '%iq2i_storia.default_path%',
            ]);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ProfilerPass());
    }
}
