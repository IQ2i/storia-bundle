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

namespace IQ2i\StoriaBundle\Tests\Mate;

use IQ2i\StoriaBundle\Mate\ConsoleRunner;
use IQ2i\StoriaBundle\Mate\StoriaTool;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

class StoriaToolTest extends TestCase
{
    public function testListsTheViews(): void
    {
        $result = $this->decode($this->createTool()->list('components/badge'));

        $this->assertArrayHasKey('_security_notice', $result);
        $views = array_column($result['untrusted_data']['views'], null, 'path');
        $this->assertSame('template', $views['components/badge']['type']);
        $this->assertSame('default', $views['components/badge']['variants'][0]['name']);
    }

    public function testRendersAVariant(): void
    {
        $result = $this->decode($this->createTool()->render('components/badge', 'default'));

        $this->assertSame('components/badge', $result['untrusted_data']['view']);
        $this->assertStringContainsString('Default</span>', $result['untrusted_data']['content']);
    }

    public function testRendersTheTemplateSource(): void
    {
        $result = $this->decode($this->createTool()->render('components/badge', 'default', 'twig'));

        $this->assertStringContainsString('{{', $result['untrusted_data']['content']);
    }

    public function testRenderFailsWithTheMessageOfTheCommand(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has no variant "nope"');

        $this->createTool()->render('components/badge', 'nope');
    }

    public function testRenderNeverTakesTheViewForAnOption(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not found');

        $this->createTool()->render('--env=prod', 'default');
    }

    public function testRenderRejectsAnUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createTool()->render('components/badge', 'default', 'pdf');
    }

    public function testTestsTheCatalog(): void
    {
        $result = $this->decode($this->createTool()->test('components/badge', snapshots: false));

        $this->assertTrue($result['untrusted_data']['passed']);
        $this->assertStringContainsString('components/badge:default', $result['untrusted_data']['output']);
    }

    public function testTestReportsAFailureWithoutThrowing(): void
    {
        $tool = new StoriaTool(new ConsoleRunner(__DIR__.'/fixtures/failing_console.php'));
        $result = $this->decode($tool->test());

        $this->assertFalse($result['untrusted_data']['passed']);
        $this->assertStringContainsString('components/broken:default', $result['untrusted_data']['output']);
    }

    public function testMissingConsole(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ai_mate_storia.console');

        (new StoriaTool(new ConsoleRunner(__DIR__.'/missing/bin/console')))->list();
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $response): array
    {
        $decoded = ResponseEncoder::decode($response);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    private function createTool(): StoriaTool
    {
        return new StoriaTool(new ConsoleRunner(__DIR__.'/../TestApplication/bin/console'));
    }
}
