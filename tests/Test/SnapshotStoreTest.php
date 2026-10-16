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

namespace IQ2i\StoriaBundle\Tests\Test;

use IQ2i\StoriaBundle\Test\Snapshot\SnapshotStore;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SnapshotStoreTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/storia_snapshots_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    public function testBuildsThePathOfASnapshot(): void
    {
        $this->assertSame($this->dir.'/components/badge/default.html', $this->createStore()->path('components/badge', 'default'));
    }

    public function testWritesANormalizedSnapshotAndReadsItBack(): void
    {
        $store = $this->createStore();
        $this->assertFalse($store->exists('components/badge', 'default'));

        $store->write('components/badge', 'default', "<b>hi</b>  \r\n");

        $this->assertTrue($store->exists('components/badge', 'default'));
        $this->assertSame("<b>hi</b>\n", $store->read('components/badge', 'default'));
    }

    public function testListsStoredSnapshots(): void
    {
        $store = $this->createStore();
        $this->assertSame([], $store->all());

        $store->write('components/badge', 'large', 'a');
        $store->write('components/form/text', 'default', 'b');

        $this->assertSame(['components/badge:large', 'components/form/text:default'], $store->all());
    }

    public function testWithPathTargetsAnotherDirectory(): void
    {
        $store = $this->createStore()->withPath($this->dir.'/other');
        $store->write('components/badge', 'default', 'a');

        $this->assertFileExists($this->dir.'/other/components/badge/default.html');
    }

    public function testReadingAMissingSnapshotFails(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->createStore()->read('components/badge', 'default');
    }

    #[DataProvider('provideUnsafePaths')]
    public function testRefusesAPathOutsideTheSnapshotDirectory(string $view, string $variant): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createStore()->path($view, $variant);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideUnsafePaths(): iterable
    {
        yield 'view traversal' => ['../../etc', 'passwd'];
        yield 'nested traversal' => ['components/../../x', 'default'];
        yield 'absolute view' => ['/etc', 'default'];
        yield 'empty segment' => ['components//badge', 'default'];
        yield 'backslash' => ['components\\badge', 'default'];
        yield 'variant traversal' => ['components/badge', '../default'];
        yield 'variant with slash' => ['components/badge', 'a/b'];
        yield 'hidden variant' => ['components/badge', '.hidden'];
    }

    private function createStore(): SnapshotStore
    {
        return new SnapshotStore($this->dir, new Filesystem());
    }
}
