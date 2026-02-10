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

use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->add('home', '/')
        ->controller(TemplateController::class)
        ->defaults(['template' => 'pages/homepage.html.twig']);

    $routes->import('@IQ2iStoriaBundle/config/routes.php')->prefix('/storia');

    if ('dev' === $routes->env()) {
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.php')->prefix('/_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.php')->prefix('/_profiler');
    }
};
