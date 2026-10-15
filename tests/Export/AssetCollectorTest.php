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

namespace IQ2i\StoriaBundle\Tests\Export;

use IQ2i\StoriaBundle\Export\AssetCollector;
use IQ2i\StoriaBundle\Export\ExportException;
use IQ2i\StoriaBundle\Export\UrlRewriter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AssetCollectorTest extends KernelTestCase
{
    private string $outputDir;

    private string $publicDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->outputDir = sys_get_temp_dir().'/storia_assets_'.bin2hex(random_bytes(4));
        $this->publicDir = __DIR__.'/../TestApplication/public';
        $this->filesystem->mkdir($this->outputDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->outputDir);
        parent::tearDown();
    }

    public function testCopiesPublicFilesAndFollowsStylesheets(): void
    {
        $count = $this->createCollector()->collect(['components/badge:default' => ['/css/test.css?v=2']], $this->outputDir);

        $this->assertSame(4, $count);
        $this->assertFileExists($this->outputDir.'/css/imported.css');
        $this->assertFileExists($this->outputDir.'/img/pixel.svg');
        $this->assertFileExists($this->outputDir.'/img/relative.svg');

        $css = (string) file_get_contents($this->outputDir.'/css/test.css');
        $this->assertStringContainsString('@import "../css/imported.css"', $css);
        $this->assertStringContainsString('url(../img/pixel.svg)', $css);
        $this->assertStringContainsString('url("../img/relative.svg")', $css);

        $this->assertStringContainsString('.storia-test {', (string) file_get_contents($this->publicDir.'/css/test.css'));
        $this->assertStringContainsString('url(/img/pixel.svg)', (string) file_get_contents($this->publicDir.'/css/test.css'), 'The public directory must stay untouched');
    }

    public function testFetchesAssetsFromTheApplication(): void
    {
        $count = $this->createCollector()->collect(['components/badge:default' => ['/dynamic/app.css']], $this->outputDir);

        $this->assertSame(2, $count);
        $this->assertSame('.storia-dynamic { background: url(../img/pixel.svg); }', file_get_contents($this->outputDir.'/dynamic/app.css'));
        $this->assertFileExists($this->outputDir.'/img/pixel.svg');
    }

    public function testExportsConfiguredGlobs(): void
    {
        $count = $this->createCollector(['img/*.svg'])->collect([], $this->outputDir);

        $this->assertSame(3, $count);
        $this->assertFileExists($this->outputDir.'/img/extra.svg');
        $this->assertFileExists($this->outputDir.'/img/pixel.svg');
    }

    public function testIgnoresBundleAssetsAndCountsEachAssetOnce(): void
    {
        $count = $this->createCollector()->collect([
            'a:default' => ['/bundles/iq2istoria/storia.css', '/img/pixel.svg'],
            'b:default' => ['/img/pixel.svg?v=1'],
        ], $this->outputDir);

        $this->assertSame(1, $count);
        $this->assertFileDoesNotExist($this->outputDir.'/bundles/iq2istoria/storia.css');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideUnsafeUrls')]
    public function testRefusesPathsOutsideThePublicDirectory(string $url): void
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('never exported');

        $this->createCollector()->collect(['components/badge:default' => [$url]], $this->outputDir);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUnsafeUrls(): iterable
    {
        yield 'parent directory' => ['/../../etc/passwd'];
        yield 'encoded parent directory' => ['/%2e%2e/%2e%2e/etc/passwd'];
        yield 'hidden file' => ['/.env'];
    }

    public function testMissingAssetFailsWithItsSource(): void
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('Asset "/missing.css" referenced by "components/badge:default" cannot be exported');

        $this->createCollector()->collect(['components/badge:default' => ['/missing.css']], $this->outputDir);
    }

    public function testHtmlAnswerIsNotMistakenForAnAsset(): void
    {
        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('answered with an HTML page');

        $this->createCollector()->collect(['components/badge:default' => ['/storia/components/badge.png']], $this->outputDir);
    }

    public function testPhpFilesAreNeverCopiedFromThePublicDirectory(): void
    {
        $this->expectException(ExportException::class);

        $this->createCollector()->collect(['components/badge:default' => ['/index.php']], $this->outputDir);
    }

    public function testSymlinksLeavingThePublicDirectoryAreNotFollowed(): void
    {
        $publicDir = $this->outputDir.'_public';
        $this->filesystem->mkdir($publicDir);
        $this->filesystem->dumpFile($this->outputDir.'_secret.txt', 'secret');
        $this->filesystem->symlink($this->outputDir.'_secret.txt', $publicDir.'/link.txt');

        try {
            $this->createCollector([], $publicDir)->collect(['components/badge:default' => ['/link.txt']], $this->outputDir);
            $this->fail('The symlink target must not be exported.');
        } catch (ExportException) {
            $this->assertFileDoesNotExist($this->outputDir.'/link.txt');
        } finally {
            $this->filesystem->remove([$publicDir, $this->outputDir.'_secret.txt']);
        }
    }

    /**
     * @param list<string> $globs
     */
    private function createCollector(array $globs = [], ?string $publicDir = null): AssetCollector
    {
        self::bootKernel();

        /** @var HttpKernelInterface $kernel */
        $kernel = static::getContainer()->get('http_kernel');

        return new AssetCollector($publicDir ?? $this->publicDir, $globs, $kernel, new UrlRewriter(), $this->filesystem);
    }
}
