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
use IQ2i\StoriaBundle\Test\Snapshot\SnapshotStore;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\IncompleteTestError;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

class StoriaTestCaseTraitTest extends KernelTestCase
{
    use StoriaTestCaseTrait;

    private string|false $ci;

    protected function setUp(): void
    {
        $this->ci = getenv('CI');
        putenv('CI');
    }

    protected function tearDown(): void
    {
        putenv(false === $this->ci ? 'CI' : 'CI='.$this->ci);
        (new Filesystem())->remove(static::getContainer()->getParameter('iq2i_storia.test.snapshot_path'));
        parent::tearDown();
    }

    #[DataProvider('provideVariants')]
    public function testVariant(string $path, string $variant): void
    {
        $view = $this->assertVariantRenders($path, $variant);

        self::assertStringNotContainsString('TODO', (string) $view->getHtmlContent());
    }

    public function testAVariantThatDoesNotExistFails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Variant "components/badge:unknown" does not exist.');

        $this->assertVariantRenders('components/badge', 'unknown');
    }

    public function testAVariantMatchingItsSnapshotPasses(): void
    {
        $store = static::getContainer()->get(SnapshotStore::class);
        $store->write('components/badge', 'default', (string) $this->renderVariant('components/badge', 'default')->getHtmlContent());

        $this->assertVariantMatchesSnapshot('components/badge', 'default');
    }

    public function testAVariantDifferingFromItsSnapshotFails(): void
    {
        static::getContainer()->get(SnapshotStore::class)->write('components/badge', 'default', "<span>outdated</span>\n");

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Variant "components/badge:default" differs from its snapshot.');

        $this->assertVariantMatchesSnapshot('components/badge', 'default');
    }

    public function testAMissingSnapshotIsWrittenAndTheTestIsIncomplete(): void
    {
        $store = static::getContainer()->get(SnapshotStore::class);

        try {
            $this->assertVariantMatchesSnapshot('components/badge', 'default');
            self::fail('The test should be incomplete.');
        } catch (IncompleteTestError $e) {
            self::assertStringContainsString('did not exist, it was written', $e->getMessage());
        }

        self::assertTrue($store->exists('components/badge', 'default'));
    }

    public function testAMissingSnapshotFailsOnCi(): void
    {
        putenv('CI=true');
        $store = static::getContainer()->get(SnapshotStore::class);

        try {
            $this->assertVariantMatchesSnapshot('components/badge', 'default');
            self::fail('The test should fail.');
        } catch (AssertionFailedError $e) {
            self::assertStringContainsString('run the test locally and commit the file', $e->getMessage());
        }

        self::assertFalse($store->exists('components/badge', 'default'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideVariants(): array
    {
        return StoriaVariants::fromDirectory(__DIR__.'/../../TestApplication/storia');
    }
}
