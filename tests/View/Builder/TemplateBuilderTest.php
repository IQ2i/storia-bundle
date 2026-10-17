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

namespace IQ2i\StoriaBundle\Tests\View\Builder;

use IQ2i\StoriaBundle\View\Builder\TemplateBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TemplateBuilderTest extends KernelTestCase
{
    public function testTemplateOutsideOfTheProjectIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside of the project directory');

        $this->builder()->build('pages/leak', 'default', $this->config('/etc/passwd'));
    }

    public function testTemplateReachedThroughADotDotSegmentIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->builder()->build('pages/leak', 'default', $this->config($this->parameter('kernel.project_dir').'/../../../../../../../../etc/passwd'));
    }

    public function testAbsoluteTemplateOfTheProjectIsRead(): void
    {
        $file = $this->parameter('iq2i_storia.default_path').'/pages/homepage/homepage.html.twig';

        $view = $this->builder()->build('pages/homepage/homepage', 'default', $this->config($file));

        $this->assertSame(file_get_contents($file), $view->getTwigContent());
    }

    private function parameter(string $name): string
    {
        $value = self::getContainer()->getParameter($name);
        \assert(\is_string($value));

        return $value;
    }

    private function builder(): TemplateBuilder
    {
        $builder = self::getContainer()->get(TemplateBuilder::class);
        \assert($builder instanceof TemplateBuilder);

        return $builder;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $template): array
    {
        return ['template' => $template, 'variants' => ['default' => ['args' => ['message' => 'Hello'], 'blocks' => []]]];
    }
}
