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

use Twig\Environment;

return static function (ContainerConfigurator $container): void {
    // The "##" documentation comments are a syntax error before Twig 3.29
    $fixtures = version_compare(Environment::VERSION, '3.29.0', '>=') ? 'templates_documented' : 'templates_plain';

    $container->extension('twig', [
        'default_path' => '%kernel.project_dir%/templates',
        'paths' => [
            '%kernel.project_dir%/'.$fixtures => null,
        ],
        'form_themes' => ['form/theme.html.twig'],
    ]);

    if ('test' === $container->env()) {
        $container->extension('twig', [
            'strict_variables' => true,
        ]);
    }
};
