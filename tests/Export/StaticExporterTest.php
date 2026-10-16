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

use IQ2i\StoriaBundle\Export\ExportException;
use IQ2i\StoriaBundle\Export\StaticExporter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

class StaticExporterTest extends KernelTestCase
{
    private string $outputDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->outputDir = sys_get_temp_dir().'/storia_export_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->outputDir);
        parent::tearDown();
    }

    public function testExportsTheWholeCatalog(): void
    {
        $progress = [];
        $result = $this->createExporter()->export($this->outputDir, static function (string $view, string $variant) use (&$progress): void {
            $progress[] = $view.':'.$variant;
        });

        $this->assertSame(14, $result->views);
        $this->assertSame(41, $result->variants);
        $this->assertCount(41, $progress);
        $this->assertContains('components/badge:default', $progress);
        $this->assertSame(6, $result->assets);

        foreach ([
            'index.html',
            '.storia-export',
            'components/badge/default.html',
            'iframe/components/badge/default.html',
            'components/product/expensive.html',
            'components/form/text/error.html',
            'pages/homepage/homepage/default.html',
            'bundles/iq2istoria/storia.css',
            'bundles/iq2istoria/storia.js',
            'bundles/iq2istoria/fonts/GeneralSans-Variable.woff2',
            'css/test.css',
            'css/imported.css',
            'img/pixel.svg',
            'img/relative.svg',
            'img/extra.svg',
            'dynamic/app.css',
        ] as $file) {
            $this->assertFileExists($this->outputDir.'/'.$file);
        }
    }

    public function testPagesAreSelfContainedAndRelative(): void
    {
        $this->createExporter()->export($this->outputDir);

        $page = $this->read('components/badge/default.html');
        $this->assertStringContainsString('href="../../bundles/iq2istoria/storia.css"', $page);
        $this->assertStringContainsString('src="../../bundles/iq2istoria/storia.js"', $page);
        $this->assertStringContainsString('src="../../iframe/components/badge/default.html"', $page);
        $this->assertStringContainsString('href="../../components/badge/large.html"', $page);
        $this->assertStringContainsString('href="../../components/avatar/rounded.html"', $page);
        $this->assertStringNotContainsString('live-reload', $page);
        $this->assertStringNotContainsString('importmap', $page);
        $this->assertStringContainsString('Static export', $page);
        $this->assertMatchesRegularExpression('#class="menu_item active">\s*<a href="../../components/badge/default.html">#', $page);

        $index = $this->read('index.html');
        $this->assertStringContainsString('href="bundles/iq2istoria/storia.css"', $index);
        $this->assertStringContainsString('href="components/badge/default.html"', $index);

        $iframe = $this->read('iframe/components/badge/default.html');
        $this->assertStringContainsString('href="../../../css/test.css"', $iframe);
        $this->assertStringContainsString('href="../../../dynamic/app.css"', $iframe);
        $this->assertStringContainsString('Default', $iframe);
    }

    public function testResolvesArgsOfComponents(): void
    {
        $this->createExporter()->export($this->outputDir);

        $this->assertStringContainsString('Expensive', $this->read('components/product/expensive.html'));
        $this->assertNotSame($this->read('iframe/components/product/default.html'), $this->read('iframe/components/product/expensive.html'));
    }

    public function testRefusesAnOutputDirectoryItDidNotCreate(): void
    {
        $this->filesystem->dumpFile($this->outputDir.'/precious.txt', 'keep me');

        try {
            $this->createExporter()->export($this->outputDir);
            $this->fail('A foreign non-empty directory must not be emptied.');
        } catch (ExportException $exportException) {
            $this->assertStringContainsString('not empty', $exportException->getMessage());
            $this->assertFileExists($this->outputDir.'/precious.txt');
        }
    }

    public function testEmptiesThePreviousExport(): void
    {
        $exporter = $this->createExporter();
        $exporter->export($this->outputDir);

        $this->filesystem->dumpFile($this->outputDir.'/stale.html', 'old');
        $this->filesystem->dumpFile($this->outputDir.'/.hidden', 'old');

        $exporter->export($this->outputDir);

        $this->assertFileDoesNotExist($this->outputDir.'/stale.html');
        $this->assertFileDoesNotExist($this->outputDir.'/.hidden');
        $this->assertFileExists($this->outputDir.'/index.html');
    }

    public function testAcceptsAnExistingEmptyDirectory(): void
    {
        $this->filesystem->mkdir($this->outputDir);

        $this->createExporter()->export($this->outputDir);

        $this->assertFileExists($this->outputDir.'/index.html');
    }

    public function testRefusesAFileAsOutput(): void
    {
        $this->filesystem->dumpFile($this->outputDir, 'a file');

        $this->expectException(ExportException::class);
        $this->expectExceptionMessage('is a file');

        $this->createExporter()->export($this->outputDir);
    }

    private function createExporter(): StaticExporter
    {
        self::bootKernel();

        return static::getContainer()->get(StaticExporter::class);
    }

    private function read(string $file): string
    {
        return (string) file_get_contents($this->outputDir.'/'.$file);
    }
}
