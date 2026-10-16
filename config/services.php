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

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Controls\PropsExtractor;
use IQ2i\StoriaBundle\Data\DataRepository;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\View\Builder\FormBuilder;
use IQ2i\StoriaBundle\View\Builder\TemplateBuilder;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use IQ2i\StoriaBundle\View\ViewBuilder;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set(YamlPreProcessor::class)

        ->set(ArgResolver::class)
            ->args([
                service_locator([]), // Will be populated by ArgResolverPass
            ])

        ->set(DataRepository::class)
            ->args([
                '%iq2i_storia.default_path%',
                service(YamlPreProcessor::class),
            ])

        ->set(DataResolver::class)
            ->args([
                service(DataRepository::class),
                service(ArgResolver::class),
            ])

        ->set(PropsExtractor::class)
            ->args([
                service('twig'),
            ])

        ->set(ControlsBuilder::class)
            ->args([
                service(PropsExtractor::class),
            ])

        ->set(ViewBuilder::class)
            ->args([
                '%iq2i_storia.default_path%',
                tagged_iterator('iq2i_storia.builder'),
                service(YamlPreProcessor::class),
            ])

        ->set(FormBuilder::class)
            ->tag('iq2i_storia.builder')
            ->args([
                '%iq2i_storia.default_path%',
                service('twig'),
                service('form.factory'),
                service(DataResolver::class),
            ])

        ->set(TemplateBuilder::class)
            ->tag('iq2i_storia.builder')
            ->args([
                '%iq2i_storia.default_path%',
                service('twig'),
                service(DataResolver::class),
                service(ControlsBuilder::class),
            ])
    ;
};
