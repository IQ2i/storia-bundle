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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('twig_component', [
        'defaults' => [
            'IQ2i\\StoriaBundle\\Tests\\TestApplication\\Twig\\Component\\' => null,
            'IQ2i\\StoriaBundle\\Tests\\TestApplication\\Twig\\Components\\' => null,
        ],
    ]);
};
