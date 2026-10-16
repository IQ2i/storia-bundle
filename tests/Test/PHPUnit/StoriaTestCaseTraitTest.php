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

use IQ2i\StoriaBundle\Test\PHPUnit\StoriaTestCaseTrait;
use IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StoriaTestCaseTraitTest extends KernelTestCase
{
    use StoriaTestCaseTrait;

    #[DataProvider('provideVariants')]
    public function testVariant(string $path, string $variant): void
    {
        $view = $this->assertVariantRenders($path, $variant);

        self::assertStringNotContainsString('TODO', (string) $view->getHtmlContent());
    }

    #[DataProvider('provideVariants')]
    public function testVariantMatchesItsSnapshot(string $path, string $variant): void
    {
        $this->assertVariantMatchesSnapshot($path, $variant);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideVariants(): array
    {
        return StoriaVariants::fromDirectory(__DIR__.'/../../TestApplication/storia');
    }
}
