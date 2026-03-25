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

use IQ2i\StoriaBundle\Command\WatchCommand;
use PHPUnit\Framework\TestCase;

class WatchCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $command = new WatchCommand('/nonexistent', [], \dirname(__DIR__, 2));

        $this->assertSame('storia:watch', $command->getName());
    }

    public function testBinaryPathContainsPlatformName(): void
    {
        $command = new WatchCommand('/nonexistent', [], \dirname(__DIR__, 2));

        $reflection = new \ReflectionMethod($command, 'getBinaryPath');
        $binaryPath = $reflection->invoke($command);

        $os = strtolower(php_uname('s'));
        if (str_contains($os, 'darwin')) {
            $this->assertStringContainsString('watcher-darwin-', $binaryPath);
        } elseif (str_contains($os, 'linux')) {
            $this->assertStringContainsString('watcher-linux-', $binaryPath);
        } else {
            $this->assertStringContainsString('watcher-windows', $binaryPath);
        }
    }

    public function testBinaryFileExists(): void
    {
        $command = new WatchCommand('/nonexistent', [], \dirname(__DIR__, 2));

        $reflection = new \ReflectionMethod($command, 'getBinaryPath');
        $binaryPath = $reflection->invoke($command);

        $this->assertFileExists($binaryPath);
    }
}
