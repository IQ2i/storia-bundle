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

use IQ2i\StoriaBundle\View\Builder\ComponentBuilder;
use IQ2i\StoriaBundle\View\Builder\TemplateBuilder;
use IQ2i\StoriaBundle\View\ViewBuilder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(ViewBuilder::class)
            ->args([
                '%iq2i_storia.default_path%',
                tagged_iterator('iq2i_storia.builder'),
            ])

        ->set(ComponentBuilder::class)
            ->tag('iq2i_storia.builder')
            ->args([
                '%iq2i_storia.default_path%',
                service('twig'),
                service('ux.twig_component.component_template_finder'),
                service('ux.twig_component.component_factory'),
            ])

        ->set(TemplateBuilder::class)
            ->tag('iq2i_storia.builder')
            ->args([
                '%iq2i_storia.default_path%',
                service('twig'),
            ])
    ;
};
