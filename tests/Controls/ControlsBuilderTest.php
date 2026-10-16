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

use IQ2i\StoriaBundle\Controls\Control;
use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Controls\PropsExtractor;
use IQ2i\StoriaBundle\Tests\Fixtures\Panel;
use IQ2i\StoriaBundle\Tests\Fixtures\PanelSize;
use Symfony\UX\TwigComponent\ComponentMetadata;

class ControlsBuilderTest extends TwigTestCase
{
    private ControlsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ControlsBuilder(new PropsExtractor($this->createTwig([
            'card.html.twig' => "{% props title = 'Card', count = 3, shouted = 'x'|upper %}",
            'panel.html.twig' => '<p></p>',
        ])));
    }

    /**
     * @param list<Control> $controls
     *
     * @return array<string, Control>
     */
    private function byName(array $controls): array
    {
        return array_combine(array_map(static fn (Control $control): string => $control->name, $controls), $controls);
    }

    private function anonymous(): ComponentMetadata
    {
        return new ComponentMetadata(['key' => 'Card', 'template' => 'card.html.twig']);
    }

    private function panel(): ComponentMetadata
    {
        return new ComponentMetadata(['key' => 'Panel', 'template' => 'panel.html.twig', 'class' => Panel::class, 'service_id' => 'panel', 'expose_public_props' => true]);
    }

    public function testTemplateArgsAreTypedByTheirYamlValue(): void
    {
        $controls = $this->builder->forTemplate(['class' => 'a b', 'rows' => 2, 'list' => ['x']]);

        $this->assertSame(['class', 'rows', 'list'], array_map(static fn (Control $control): string => $control->name, $controls));
        $this->assertSame(['text', 'number', 'json'], array_map(static fn (Control $control): string => $control->type, $controls));
        $this->assertSame(['yaml'], array_values(array_unique(array_map(static fn (Control $control): string => $control->source, $controls))));
        $this->assertContainsOnly('bool', array_map(static fn (Control $control): bool => $control->inYaml, $controls));
    }

    public function testAnonymousComponentGetsOneControlPerProp(): void
    {
        $controls = $this->builder->forComponent($this->anonymous(), []);

        $this->assertSame(['title', 'count', 'shouted'], array_keys($this->byName($controls)));
        $this->assertSame('text', $controls[0]->type);
        $this->assertSame('Card', $controls[0]->value);
        $this->assertSame('Card', $controls[0]->default);
        $this->assertSame('props', $controls[0]->source);
        $this->assertFalse($controls[0]->inYaml);
        $this->assertSame('number', $controls[1]->type);
    }

    public function testExpressionPropWithoutYamlValueIsReadOnly(): void
    {
        $shouted = $this->byName($this->builder->forComponent($this->anonymous(), []))['shouted'];

        $this->assertFalse($shouted->editable);
        $this->assertSame('expression', $shouted->note);
        $this->assertNull($shouted->value);
    }

    public function testYamlArgReplacesTheValueButKeepsTheDefault(): void
    {
        $controls = $this->byName($this->builder->forComponent($this->anonymous(), ['title' => 'From YAML', 'shouted' => 'Y']));

        $this->assertSame('From YAML', $controls['title']->value);
        $this->assertSame('From YAML', $controls['title']->current);
        $this->assertSame('Card', $controls['title']->default);
        $this->assertSame('props', $controls['title']->source);
        $this->assertTrue($controls['title']->inYaml);
        $this->assertTrue($controls['shouted']->editable);
    }

    public function testRemainingYamlArgsComeLast(): void
    {
        $controls = $this->builder->forComponent($this->anonymous(), ['extra' => 1, 'title' => 'T']);

        $this->assertSame(['title', 'count', 'shouted', 'extra'], array_map(static fn (Control $control): string => $control->name, $controls));
        $this->assertSame('yaml', end($controls)->source);
    }

    public function testPublicPropertiesOfTheComponentClass(): void
    {
        $controls = $this->byName($this->builder->forComponent($this->panel(), ['rows' => 5]));

        $this->assertSame(['title', 'rows', 'closable', 'size', 'items', 'date', 'required'], array_keys($controls));
        $this->assertSame('class', $controls['title']->source);
        $this->assertSame(5, $controls['rows']->value);
        $this->assertSame(2, $controls['rows']->default);
        $this->assertSame('select', $controls['size']->type);
        $this->assertSame('small', $controls['size']->value);
        $this->assertFalse($controls['date']->editable);
        $this->assertNull($controls['required']->default);
    }

    public function testPublicPropertiesAreIgnoredWhenNotExposed(): void
    {
        $metadata = new ComponentMetadata(['key' => 'Panel', 'template' => 'panel.html.twig', 'class' => Panel::class, 'service_id' => 'panel', 'expose_public_props' => false]);

        $this->assertSame([], $this->builder->forComponent($metadata, []));
    }

    public function testOverridesAreCoercedToTheTypeOfTheControl(): void
    {
        $controls = $this->builder->forComponent($this->panel(), []);

        $result = $this->builder->applyOverrides($controls, [
            'title' => 'Hello',
            'rows' => '7',
            'closable' => true,
            'size' => 'large',
            'items' => ['a' => 1],
        ]);

        $this->assertSame(['title' => 'Hello', 'rows' => 7, 'closable' => true, 'size' => PanelSize::Large, 'items' => ['a' => 1]], $result['overrides']);

        $current = $this->byName($result['controls']);
        $this->assertSame('Hello', $current['title']->current);
        $this->assertSame('Panel', $current['title']->value);
        $this->assertSame('large', $current['size']->current);
    }

    public function testInvalidOrUnknownOverridesAreDropped(): void
    {
        $controls = $this->builder->forComponent($this->panel(), []);

        $result = $this->builder->applyOverrides($controls, [
            'unknown' => 'x',
            'rows' => 'many',
            'closable' => 'yes',
            'size' => 'huge',
            'items' => 'not an array',
            'title' => ['array'],
            'date' => 'now',
            0 => 'numeric key',
        ]);

        $this->assertSame([], $result['overrides']);
    }

    public function testOverridesOfReadOnlyControlsAreDropped(): void
    {
        $controls = $this->builder->forComponent($this->anonymous(), []);

        $this->assertSame([], $this->builder->applyOverrides($controls, ['shouted' => 'y'])['overrides']);
    }

    public function testOverridesWithAnUnsafeNameAreDropped(): void
    {
        $controls = $this->builder->forTemplate(["a'] }} {{ 7*7 }} {{ x['" => 'v', 'ok-name' => 'v']);

        $result = $this->builder->applyOverrides($controls, ["a'] }} {{ 7*7 }} {{ x['" => 'w', 'ok-name' => 'w']);

        $this->assertSame(['ok-name' => 'w'], $result['overrides']);
    }
}
