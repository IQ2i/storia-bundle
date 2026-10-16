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

use IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class TestCommandTest extends KernelTestCase
{
    private string $snapshotDir;

    private string|false $ci;

    protected function setUp(): void
    {
        $this->snapshotDir = sys_get_temp_dir().'/storia_test_command_'.bin2hex(random_bytes(4));

        // The suite itself runs on CI runners, where the variable would change the behaviour under test
        $this->ci = getenv('CI');
        putenv('CI');
    }

    protected function tearDown(): void
    {
        putenv(false === $this->ci ? 'CI' : 'CI='.$this->ci);
        (new Filesystem())->remove($this->snapshotDir);
        parent::tearDown();
    }

    private function variantCount(): int
    {
        return \count(StoriaVariants::fromDirectory(__DIR__.'/../TestApplication/storia'));
    }

    public function testTheVersionedSnapshotsOfTheTestApplicationAreUpToDate(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['--ci' => true]);

        $this->assertSame(0, $exitCode, $tester->getDisplay());
        $this->assertStringContainsString(\sprintf('%1$d variants, %1$d passed, 0 new, 0 updated, 0 failed', $this->variantCount()), $tester->getDisplay());
        $this->assertStringNotContainsString('orphan snapshot', $tester->getDisplay());
    }

    public function testWritesNewSnapshotsWithoutFailing(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('+ components/badge:default (new snapshot)', $tester->getDisplay());
        $this->assertStringContainsString(\sprintf('%1$d variants, 0 passed, %1$d new, 0 updated, 0 failed', $this->variantCount()), $tester->getDisplay());
        $this->assertFileExists($this->snapshotDir.'/components/badge/default.html');
    }

    public function testMissingSnapshotsFailWithTheCiOption(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir, '--ci' => true, '--filter' => 'components/badge:default']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('✗ components/badge:default', $tester->getDisplay());
        $this->assertStringContainsString('No snapshot: run storia:test locally and commit the file.', $tester->getDisplay());
        $this->assertStringContainsString('1 variants, 0 passed, 0 new, 0 updated, 1 failed', $tester->getDisplay());
        $this->assertDirectoryDoesNotExist($this->snapshotDir);
    }

    public function testTheCiEnvironmentVariableEnablesTheCiMode(): void
    {
        putenv('CI=true');

        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir, '--filter' => 'components/badge:default']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('No snapshot', $tester->getDisplay());
        $this->assertDirectoryDoesNotExist($this->snapshotDir);
    }

    public function testPassesWhenSnapshotsMatch(): void
    {
        $this->createTester()->execute(['--snapshot-path' => $this->snapshotDir]);

        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('✓ components/badge:default', $tester->getDisplay());
        $this->assertStringContainsString(\sprintf('%1$d variants, %1$d passed, 0 new, 0 updated, 0 failed', $this->variantCount()), $tester->getDisplay());
    }

    public function testFilterRestrictsTheVariants(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir, '--filter' => 'components/product:expensive']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('components/product:expensive', $tester->getDisplay());
        $this->assertStringNotContainsString('components/badge', $tester->getDisplay());
        $this->assertStringContainsString('1 variants, 0 passed, 1 new', $tester->getDisplay());
    }

    public function testFailsOnASnapshotMismatchAndShowsTheDiff(): void
    {
        $this->createTester()->execute(['--snapshot-path' => $this->snapshotDir, '--filter' => 'components/badge:default']);
        file_put_contents($this->snapshotDir.'/components/badge/default.html', "<span>outdated</span>\n");

        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir, '--filter' => 'components/badge:default']);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('≠ components/badge:default', $tester->getDisplay());
        $this->assertStringContainsString('-<span>outdated</span>', $tester->getDisplay());
        $this->assertStringContainsString('1 failed', $tester->getDisplay());
        $this->assertStringEqualsFile($this->snapshotDir.'/components/badge/default.html', "<span>outdated</span>\n");
    }

    public function testUpdateRewritesTheDifferingSnapshots(): void
    {
        $this->createTester()->execute(['--snapshot-path' => $this->snapshotDir, '--filter' => 'components/badge:default']);
        file_put_contents($this->snapshotDir.'/components/badge/default.html', "<span>outdated</span>\n");

        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir, '--filter' => 'components/badge:default', '--update' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('↻ components/badge:default (snapshot updated)', $tester->getDisplay());
        $this->assertStringNotContainsString('outdated', (string) file_get_contents($this->snapshotDir.'/components/badge/default.html'));
    }

    public function testNoSnapshotsOnlyChecksTheRendering(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir, '--no-snapshots' => true, '--ci' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString(\sprintf('%1$d variants, %1$d passed, 0 new, 0 updated, 0 failed', $this->variantCount()), $tester->getDisplay());
        $this->assertDirectoryDoesNotExist($this->snapshotDir);
    }

    public function testReportsOrphanSnapshotsWithoutFailing(): void
    {
        $this->createTester()->execute(['--snapshot-path' => $this->snapshotDir]);
        file_put_contents($this->snapshotDir.'/components/badge/removed.html', "<i></i>\n");

        $tester = $this->createTester();
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('? components/badge:removed (orphan snapshot', $tester->getDisplay());
    }

    public function testFailsWithTheNameOfTheBrokenVariant(): void
    {
        $tester = $this->createTester('export_failure');
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('✗ components/broken:default', $tester->getDisplay());
        $this->assertStringContainsString('missing.html.twig', $tester->getDisplay());
        $this->assertStringContainsString('1 variants, 0 passed, 0 new, 0 updated, 1 failed', $tester->getDisplay());
    }

    public function testReportsViewsWithoutVariant(): void
    {
        $tester = $this->createTester('empty_view');
        $exitCode = $tester->execute(['--snapshot-path' => $this->snapshotDir]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('? components/empty (no variant, nothing to test)', $tester->getDisplay());

        $tester = $this->createTester('empty_view');
        $this->assertSame(1, $tester->execute(['--snapshot-path' => $this->snapshotDir, '--ci' => true]));
        $this->assertStringContainsString('0 variants, 0 passed, 0 new, 0 updated, 1 failed', $tester->getDisplay());
    }

    public function testDefaultSnapshotPathComesFromTheConfiguration(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame($kernel->getProjectDir().'/storia/snapshots', $kernel->getContainer()->getParameter('iq2i_storia.test.snapshot_path'));
    }

    private function createTester(string $environment = 'test'): CommandTester
    {
        $kernel = self::bootKernel(['environment' => $environment]);

        return new CommandTester((new Application($kernel))->find('storia:test'));
    }
}
