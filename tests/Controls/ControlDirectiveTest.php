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

namespace IQ2i\StoriaBundle\Tests\Controls;

use IQ2i\StoriaBundle\Controls\ControlDirective;
use PHPUnit\Framework\TestCase;

class ControlDirectiveTest extends TestCase
{
    public function testNothingToParse(): void
    {
        $this->assertNull(ControlDirective::parse(null));
        $this->assertNull(ControlDirective::parse('   '));
        $this->assertNull(ControlDirective::parse("/**\n *\n */"));
    }

    public function testDescriptionOnly(): void
    {
        $directive = ControlDirective::parse("  The title\n  of the card ");

        $this->assertSame('The title of the card', $directive->description);
        $this->assertNull($directive->type);
        $this->assertSame([], $directive->options);
        $this->assertSame([], $directive->choices);
    }

    public function testTypeWithOptions(): void
    {
        $directive = ControlDirective::parse('Padding in rem @control Range min=0 max=4 step=0.5');

        $this->assertSame('Padding in rem', $directive->description);
        $this->assertSame('range', $directive->type);
        $this->assertSame(['min' => 0.0, 'max' => 4.0, 'step' => 0.5], $directive->options);
        $this->assertSame(0.5, $directive->number('step'));
        $this->assertNull($directive->number('unknown'));
    }

    public function testTypeWithChoices(): void
    {
        $directive = ControlDirective::parse('@control radio left|center|right');

        $this->assertNull($directive->description);
        $this->assertSame('radio', $directive->type);
        $this->assertSame(['left', 'center', 'right'], $directive->choices);
    }

    public function testDirectiveWithoutType(): void
    {
        $directive = ControlDirective::parse('Just a text @control');

        $this->assertSame('Just a text', $directive->description);
        $this->assertNull($directive->type);
    }

    public function testPhpDocblockMarkersAreStripped(): void
    {
        $directive = ControlDirective::parse("/**\n     * Accent color\n     * @control color\n     */");

        $this->assertSame('Accent color', $directive->description);
        $this->assertSame('color', $directive->type);

        $this->assertSame('Inline', ControlDirective::parse('/** Inline */')->description);
    }

    public function testNonNumericOptionsAreKeptAsStrings(): void
    {
        $directive = ControlDirective::parse('@control text format=slug');

        $this->assertSame(['format' => 'slug'], $directive->options);
        $this->assertNull($directive->number('format'));
    }
}
