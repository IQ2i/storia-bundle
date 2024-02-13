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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IQ2i\ArquiBundle\ArgumentResolver\ComponentValueResolver;
use IQ2i\ArquiBundle\Controller\IframeController;
use IQ2i\ArquiBundle\Controller\StoryController;
use IQ2i\ArquiBundle\Factory\ComponentFactory;
use IQ2i\ArquiBundle\Factory\MenuFactory;
use Symfony\Component\HttpKernel\Profiler\Profiler;

return static function (ContainerConfigurator $container) {
    $services = $container
        ->services()
        ->defaults()
        ->private();

    $services
        ->set(ComponentValueResolver::class)
            ->tag('controller.argument_value_resolver', ['priority' => 150])
            ->arg(0, service(ComponentFactory::class))

        ->set(IframeController::class)
            ->tag('controller.service_arguments')
            ->arg(0, service('twig'))

        ->set(StoryController::class)
            ->tag('controller.service_arguments')
            ->arg(0, service(MenuFactory::class))
            ->arg(1, service('twig'))
            ->arg(2, service('router'))

        ->set(ComponentFactory::class)
            ->arg(0, param('arqui_bundle.default_path'))
            ->arg(1, service('twig'))

        ->set(MenuFactory::class)
            ->arg(0, param('arqui_bundle.default_path'))
            ->arg(1, service('router'))

        ->alias(Profiler::class, 'profiler');
};
