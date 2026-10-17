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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RenderCommandTest extends KernelTestCase
{
    public function testPrintsTheHtmlOfAVariant(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['view' => 'components/badge', 'variant' => 'default']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringStartsWith('<span class="bg-blue-100', $tester->getDisplay());
        $this->assertStringContainsString('Default</span>', $tester->getDisplay());
    }

    public function testPrintsTheTwigSource(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['view' => 'components/badge', 'variant' => 'default', '--format' => 'twig']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('{{', $tester->getDisplay());
    }

    public function testPrintsTheIncludeOfAComponent(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['view' => 'components/button/button', 'variant' => 'plain', '--format' => 'include']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Button', $tester->getDisplay());
    }

    public function testFailsWhenTheViewHasNoSuchContent(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::FAILURE, $tester->execute(['view' => 'components/badge', 'variant' => 'default', '--format' => 'include']));
        $this->assertStringContainsString('has no "include" content', $tester->getDisplay());
    }

    public function testFailsOnAnUnknownView(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::FAILURE, $tester->execute(['view' => 'components/unknown', 'variant' => 'default']));
        $this->assertStringContainsString('View "components/unknown" not found', $tester->getDisplay());
    }

    public function testFailsOnAnUnknownVariant(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::FAILURE, $tester->execute(['view' => 'components/badge', 'variant' => 'nope']));
        $this->assertStringContainsString('has no variant "nope"', $tester->getDisplay());
    }

    public function testFailsWithTheErrorOfABrokenVariant(): void
    {
        $tester = $this->createTester('export_failure');

        $this->assertSame(Command::FAILURE, $tester->execute(['view' => 'components/broken', 'variant' => 'default']));
        $this->assertStringContainsString('missing.html.twig', $tester->getDisplay());
    }

    public function testRejectsAnUnknownFormat(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::INVALID, $tester->execute(['view' => 'components/badge', 'variant' => 'default', '--format' => 'pdf']));
    }

    private function createTester(string $environment = 'test'): CommandTester
    {
        $kernel = self::bootKernel(['environment' => $environment]);

        return new CommandTester((new Application($kernel))->find('storia:render'));
    }
}
