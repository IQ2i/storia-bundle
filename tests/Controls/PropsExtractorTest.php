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

use IQ2i\StoriaBundle\Controls\PropsExtractor;

class PropsExtractorTest extends TwigTestCase
{
    private function extract(string $template): array
    {
        return (new PropsExtractor($this->createTwig(['t.html.twig' => $template])))->extract('t.html.twig');
    }

    public function testConstantDefaults(): void
    {
        $props = $this->extract("{% props title = 'Hi', count = 3, ratio = -1.5, on = true, off = false, none = null %}<p>{{ title }}</p>");

        $this->assertSame(['title', 'count', 'ratio', 'on', 'off', 'none'], array_keys($props));
        $this->assertSame('Hi', $props['title']->default);
        $this->assertSame(3, $props['count']->default);
        $this->assertSame(-1.5, $props['ratio']->default);
        $this->assertTrue($props['on']->default);
        $this->assertFalse($props['off']->default);
        $this->assertNull($props['none']->default);

        foreach ($props as $prop) {
            $this->assertTrue($prop->hasDefault);
            $this->assertFalse($prop->isExpression);
        }
    }

    public function testPropWithoutDefault(): void
    {
        $props = $this->extract('{% props label %}');

        $this->assertFalse($props['label']->hasDefault);
        $this->assertFalse($props['label']->isExpression);
    }

    public function testConstantArrays(): void
    {
        $props = $this->extract("{% props items = ['a', 'b'], map = {k: 1, n: [true]} %}");

        $this->assertSame(['a', 'b'], $props['items']->default);
        $this->assertSame(['k' => 1, 'n' => [true]], $props['map']->default);
    }

    public function testExpressionsAreFlagged(): void
    {
        $props = $this->extract("{% props upper = 'x'|upper, mixed = [1, foo], other = a ~ b %}");

        foreach ($props as $prop) {
            $this->assertTrue($prop->isExpression);
            $this->assertFalse($prop->hasDefault);
            $this->assertNull($prop->default);
        }
    }

    public function testTemplateWithoutProps(): void
    {
        $this->assertSame([], $this->extract('<p>{{ title }}</p>'));
    }
}
