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

use IQ2i\StoriaBundle\View\ViewBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormBuilderTest extends KernelTestCase
{
    public function testExpandedChoiceIsRenderedWithoutCsrfTokenOutsideOfARequest(): void
    {
        $html = $this->render('expanded');

        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringNotContainsString('_token', $html);
    }

    public function testRenderingIsDeterministic(): void
    {
        $this->assertSame($this->render('expanded'), $this->render('expanded'));
    }

    private function render(string $variant): string
    {
        $view = self::getContainer()->get(ViewBuilder::class)->create('components/form/choice', $variant);

        return (string) $view?->getHtmlContent();
    }
}
