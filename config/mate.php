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

use IQ2i\StoriaBundle\Mate\ConsoleRunner;
use IQ2i\StoriaBundle\Mate\StoriaTool;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $configurator->parameters()
        ->set('ai_mate_storia.console', '%mate.root_dir%/bin/console');

    $configurator->services()
        ->set(ConsoleRunner::class)
            ->args(['%ai_mate_storia.console%'])

        ->set(StoriaTool::class)
            ->args([service(ConsoleRunner::class)]);
};
