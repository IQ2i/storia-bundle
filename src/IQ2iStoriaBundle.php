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

use IQ2i\StoriaBundle\Controller\ComponentController;
use IQ2i\StoriaBundle\Controller\IframeController;
use IQ2i\StoriaBundle\Menu\MenuBuilder;
use IQ2i\StoriaBundle\Twig\MenuExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Profiler\Profiler;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
        $container->parameters()->set('iq2i_storia.default_path', $config['default_path']);
        $container->parameters()->set('iq2i_storia.enabled', $config['enabled']);

        $container->services()->set(IframeController::class)
            ->tag('controller.service_arguments')
            ->args([
                service(ComponentFactory::class),
                service('twig'),
            ]);
        $container->services()->set(ComponentController::class)
            ->tag('controller.service_arguments')
            ->args([
                service(ComponentFactory::class),
                service('twig'),
                service('router'),
            ]);

        $container->services()->set(MenuBuilder::class)
            ->args([
                param('iq2i_storia.default_path'),
                service('router'),
            ]);

        $container->services()->set(MenuExtension::class)
            ->tag('twig.extension')
            ->args([
                service(MenuBuilder::class),
            ]);

        $container->services()->set(ComponentFactory::class)
            ->args([
                param('iq2i_storia.default_path'),
                service('twig'),
                service('ux.twig_component.component_template_finder'),
            ]);

        $container->services()->alias(Profiler::class, 'profiler');
    }
}
