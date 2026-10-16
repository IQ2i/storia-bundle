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

namespace IQ2i\StoriaBundle\Tests\Twig;

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Data\DataRepository;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\Twig\ViewExtension;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Test\IntegrationTestCase;

class ViewExtensionTest extends IntegrationTestCase
{
    protected function getFixturesDir(): string
    {
        return __DIR__.'/fixtures';
    }

    // Required by recent Twig versions, which no longer fall back on getFixturesDir()
    protected static function getFixturesDirectory(): string
    {
        return __DIR__.'/fixtures';
    }

    protected function getExtensions(): iterable
    {
        $path = __DIR__.'/../TestApplication/storia';
        $preProcessor = new YamlPreProcessor();

        yield new ViewExtension(
            $path,
            $preProcessor,
            new DataResolver(new DataRepository($path, $preProcessor), new ArgResolver(new ServiceLocator([]))),
        );
    }
}
