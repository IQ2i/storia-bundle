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

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Symfony\Symfony61\Rector\Class_\MagicClosureTwigExtensionToNativeMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(codeQuality: true, codingStyle: true)
    ->withPhpSets(php83: true)
    ->withComposerBased(phpunit: true, symfony: true)
    ->withSkip([
        __DIR__.'/tests/TestApplication/*',
        FlipTypeControlToUseExclusiveTypeRector::class,
        ArrayToFirstClassCallableRector::class => [
            __DIR__.'/src/Twig',
        ],
        MagicClosureTwigExtensionToNativeMethodsRector::class => [
            __DIR__.'/src/Twig',
        ],
    ]);
