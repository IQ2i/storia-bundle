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

use IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants;
use IQ2i\StoriaBundle\Test\VariantRunner;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VariantRunnerTest extends KernelTestCase
{
    public function testListsEveryVariantOfTheCatalog(): void
    {
        $variantsByView = $this->createRunner()->findVariants();

        $dataSets = StoriaVariants::fromDirectory(__DIR__.'/../TestApplication/storia');
        $this->assertCount(\count(array_unique(array_column($dataSets, 0))), $variantsByView);
        $this->assertSame(\count($dataSets), array_sum(array_map(count(...), $variantsByView)));
        $this->assertContains('default', $variantsByView['components/badge']);
        $this->assertSame(['default', 'disabled', 'error'], $variantsByView['components/form/text']);
    }

    public function testFiltersOnTheViewPath(): void
    {
        $variantsByView = $this->createRunner()->findVariants('components/badge');

        $this->assertSame(['components/badge', 'components/badge_extended', 'components/badge_with_include'], array_keys($variantsByView));
    }

    public function testFiltersOnTheVariant(): void
    {
        $variantsByView = $this->createRunner()->findVariants('components/product:expensive');

        $this->assertSame(['components/product' => ['expensive']], $variantsByView);
    }

    public function testFilterWithoutMatchReturnsNothing(): void
    {
        $this->assertSame([], $this->createRunner()->findVariants('does-not-exist'));
    }

    public function testRendersEveryVariant(): void
    {
        $runner = $this->createRunner();
        $rendered = [];

        $result = $runner->run($runner->findVariants('components/badge'), static function (string $path, ?string $variant, $view, $error) use (&$rendered): void {
            $rendered[] = $path.':'.$variant;
            self::assertNull($error);
            self::assertNotNull($view?->getHtmlContent());
        });

        $this->assertSame([], $result->failures);
        $this->assertSame(3, $result->views);
        $this->assertCount($result->variants, $rendered);
        $this->assertContains('components/badge:default', $rendered);
    }

    public function testReportsErrorsWithoutStopping(): void
    {
        $runner = $this->createRunner('export_failure');
        $seen = [];

        $result = $runner->run($runner->findVariants(), static function (string $path, ?string $variant, $view, ?\Throwable $error) use (&$seen): void {
            $seen[] = $path.':'.$variant;
        });

        $this->assertSame(['components/broken:default'], $seen);
        $this->assertCount(1, $result->failures);
        $this->assertSame('components/broken:default', $result->failures[0]->name());
        $this->assertStringContainsString('missing.html.twig', $result->failures[0]->exception->getMessage());
    }

    private function createRunner(string $environment = 'test'): VariantRunner
    {
        self::bootKernel(['environment' => $environment]);

        return static::getContainer()->get(VariantRunner::class);
    }
}
