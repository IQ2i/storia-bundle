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

class ListCommandTest extends KernelTestCase
{
    public function testListsViewsAndVariantsAsText(): void
    {
        $tester = $this->createTester();
        $exitCode = $tester->execute(['--filter' => 'components/progress']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('components/progress (template)', $tester->getDisplay());
        $this->assertStringContainsString('- extra_large', $tester->getDisplay());
    }

    public function testListsTheCatalogAsJson(): void
    {
        $tester = $this->createTester();
        $tester->execute(['--format' => 'json']);

        $views = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        $byPath = array_column($views, null, 'path');

        $this->assertArrayHasKey('components/badge', $byPath);
        $this->assertSame('template', $byPath['components/badge']['type']);
        $this->assertSame('ui/badge.html.twig', $byPath['components/badge']['source']);
        $this->assertStringEndsWith('storia/components/badge.yaml', $byPath['components/badge']['file']);
        $this->assertSame('component', $byPath['components/product']['type']);
        $this->assertSame('form', $byPath['components/form/choice']['type']);
    }

    public function testResolvesTheArgumentsOfAVariant(): void
    {
        $tester = $this->createTester();
        $tester->execute(['--format' => 'json', '--filter' => 'components/product:from_data']);

        $views = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertCount(1, $views);
        $this->assertSame([['name' => 'from_data', 'args' => ['product' => ['name' => 'Chair', 'price' => 999, 'inStock' => true]], 'error' => null]], $views[0]['variants']);
    }

    public function testFormVariantsExposeTheirOptions(): void
    {
        $tester = $this->createTester();
        $tester->execute(['--format' => 'json', '--filter' => 'components/form/choice:default']);

        $views = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('choices', $views[0]['variants'][0]['args']);
    }

    public function testReportsABrokenViewInsteadOfFailing(): void
    {
        $tester = $this->createTester('export_failure');
        $exitCode = $tester->execute(['--format' => 'json']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $views = json_decode($tester->getDisplay(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertSame('components/broken', $views[0]['path']);
    }

    public function testRejectsAnUnknownFormat(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::INVALID, $tester->execute(['--format' => 'xml']));
        $this->assertStringContainsString('Unknown format "xml"', $tester->getDisplay());
    }

    private function createTester(string $environment = 'test'): CommandTester
    {
        $kernel = self::bootKernel(['environment' => $environment]);

        return new CommandTester((new Application($kernel))->find('storia:list'));
    }
}
