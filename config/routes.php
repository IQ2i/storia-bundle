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

use IQ2i\StoriaBundle\Controller\IframeController;
use IQ2i\StoriaBundle\Controller\ViewController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->add('iq2i_storia_iframe', '/iframe/{view<.+>?}')
        ->controller(IframeController::class)
        ->condition('"%iq2i_storia.enabled%"');

    $routes->add('iq2i_storia_view', '/{view<.+>?}')
        ->controller(ViewController::class)
        ->condition('"%iq2i_storia.enabled%"');
};
