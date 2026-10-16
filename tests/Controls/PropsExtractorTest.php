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

    public function testDocumentationCommentsOfTheProps(): void
    {
        self::requirePropDocumentation();

        $props = $this->extract("{% props\n ## The title @control textarea\n title = 'Hi',\n count = 3,\n ## Required one\n label\n%}");

        $this->assertSame('The title', $props['title']->directive?->description);
        $this->assertSame('textarea', $props['title']->directive?->type);
        $this->assertNull($props['count']->directive);
        $this->assertSame('Required one', $props['label']->directive?->description);
        $this->assertFalse($props['label']->hasDefault);
    }

    public function testPropsAreParsedOnce(): void
    {
        $twig = $this->createTwig(['t.html.twig' => '{% props a = 1 %}']);
        $extractor = new PropsExtractor($twig);

        $this->assertSame($extractor->extract('t.html.twig'), $extractor->extract('t.html.twig'));
        $this->assertSame(['a'], array_keys($extractor->extract('t.html.twig')));
    }

    public function testTypesOfATemplate(): void
    {
        $extractor = new PropsExtractor($this->createTwig([]));

        $types = $extractor->types("{% types {\n ## The message @control textarea\n message: 'string',\n columns?: 'number',\n} %}<p>{{ message }}</p>");

        $this->assertSame(['message', 'columns'], array_keys($types));
        $this->assertSame('string', $types['message']->type);
        $this->assertFalse($types['message']->optional);
        $this->assertSame('The message', $types['message']->directive?->description);
        $this->assertSame('textarea', $types['message']->directive?->type);
        $this->assertSame('number', $types['columns']->type);
        $this->assertTrue($types['columns']->optional);
        $this->assertNull($types['columns']->directive);

        $this->assertSame([], $extractor->types('<p>{{ message }}</p>'));
    }

    public function testTypesOfAChildTemplate(): void
    {
        $extractor = new PropsExtractor($this->createTwig(['base.html.twig' => '{% block body %}{% endblock %}']));

        $types = $extractor->types("{% extends 'base.html.twig' %}{% types { message: 'string' } %}{% block body %}{{ message }}{% endblock %}");

        $this->assertSame(['message'], array_keys($types));
    }
}
