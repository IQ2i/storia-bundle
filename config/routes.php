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

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes
        ->add('iq2i_arqui_story', '/stories/{component<.+>?}')
        ->controller('iq2i_arqui.controller.story')

        ->add('iq2i_arqui_iframe', '/iframe/{component<.+>}')
        ->controller('iq2i_arqui.controller.iframe');
};
