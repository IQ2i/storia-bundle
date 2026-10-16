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

use IQ2i\StoriaBundle\Twig\ViewExtension;
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
        yield new ViewExtension(__DIR__.'/../TestApplication/storia');
    }
}
