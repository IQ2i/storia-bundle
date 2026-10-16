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

namespace IQ2i\StoriaBundle\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ExportCommandTest extends KernelTestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir().'/storia_command_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->outputDir);
        parent::tearDown();
    }

    public function testExportsToTheGivenDirectory(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['output-dir' => $this->outputDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Exported components/badge:default', $tester->getDisplay());
        $this->assertStringContainsString('Exported 14 views, 41 variants and 6 assets to '.$this->outputDir, $tester->getDisplay());
        $this->assertFileExists($this->outputDir.'/index.html');
    }

    public function testDefaultOutputDirectoryComesFromTheConfiguration(): void
    {
        $command = (new Application(self::bootKernel()))->find('storia:export');

        $this->assertStringEndsWith('/var/storia/export', (string) $command->getDefinition()->getArgument('output-dir')->getDefault());
    }

    public function testFailsWithTheNameOfTheBrokenVariant(): void
    {
        $tester = $this->createTester('export_failure');
        $exitCode = $tester->execute(['output-dir' => $this->outputDir]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Variant "components/broken:default" failed', $tester->getDisplay());
        $this->assertStringContainsString('missing.html.twig', $tester->getDisplay());
    }

    public function testFailsOnAForeignDirectory(): void
    {
        (new Filesystem())->dumpFile($this->outputDir.'/precious.txt', 'keep me');

        $tester = $this->createTester();
        $exitCode = $tester->execute(['output-dir' => $this->outputDir]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('refusing to overwrite', $tester->getDisplay());
        $this->assertFileExists($this->outputDir.'/precious.txt');
    }

    private function createTester(string $environment = 'test'): CommandTester
    {
        $kernel = self::bootKernel(['environment' => $environment]);

        return new CommandTester((new Application($kernel))->find('storia:export'));
    }
}
