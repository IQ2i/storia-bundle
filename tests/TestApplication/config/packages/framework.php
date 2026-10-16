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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => 'secret',
        'session' => [
            'handler_id' => null,
        ],
    ]);

    if (\in_array($container->env(), ['test', 'export_failure', 'forced_enabled'], true)) {
        $container->extension('framework', [
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
            ],
            'test' => true,
        ]);
    }
};
