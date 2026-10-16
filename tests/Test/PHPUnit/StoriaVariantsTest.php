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

namespace IQ2i\StoriaBundle\Tests\Test\PHPUnit;

use IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants;
use PHPUnit\Framework\TestCase;

class StoriaVariantsTest extends TestCase
{
    public function testListsTheVariantsOfEveryView(): void
    {
        $dataSets = StoriaVariants::fromDirectory($this->storiaPath());

        $this->assertCount(41, $dataSets);
        $this->assertSame(['components/badge', 'default'], $dataSets['components/badge:default']);
        $this->assertSame(['components/product', 'expensive'], $dataSets['components/product:expensive']);
    }

    public function testFormViewsHaveTheThreeFixedVariants(): void
    {
        $dataSets = StoriaVariants::fromDirectory($this->storiaPath(), 'components/form/text:');

        $this->assertSame(['components/form/text:default', 'components/form/text:disabled', 'components/form/text:error'], array_keys($dataSets));
    }

    public function testFilterRestrictsTheDataSets(): void
    {
        $this->assertSame(['components/product:expensive'], array_keys(StoriaVariants::fromDirectory($this->storiaPath(), 'product:expensive')));
        $this->assertSame([], StoriaVariants::fromDirectory($this->storiaPath(), 'does-not-exist'));
    }

    public function testProvideReadsThePathFromTheEnvironment(): void
    {
        putenv('STORIA_PATH='.$this->storiaPath());

        try {
            $this->assertCount(41, StoriaVariants::provide());
        } finally {
            putenv('STORIA_PATH');
        }
    }

    public function testAMissingDirectoryHasNoVariant(): void
    {
        $this->assertSame([], StoriaVariants::fromDirectory(__DIR__.'/does-not-exist'));
    }

    private function storiaPath(): string
    {
        return __DIR__.'/../../TestApplication/storia';
    }
}
