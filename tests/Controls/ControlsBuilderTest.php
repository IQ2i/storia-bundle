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

use IQ2i\StoriaBundle\Controls\ComponentIntrospector;
use IQ2i\StoriaBundle\Controls\Control;
use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Controls\PropsExtractor;
use IQ2i\StoriaBundle\Tests\Fixtures\Panel;
use IQ2i\StoriaBundle\Tests\Fixtures\PanelSize;
use IQ2i\StoriaBundle\Tests\Fixtures\Toast;
use Symfony\UX\TwigComponent\ComponentMetadata;

class ControlsBuilderTest extends TwigTestCase
{
    private ControlsBuilder $builder;

    protected function setUp(): void
    {
        $extractor = new PropsExtractor($this->createTwig([
            'card.html.twig' => "{% props title = 'Card', count = 3, shouted = 'x'|upper %}",
            'documented.html.twig' => "{% props\n ## Accent @control color\n accent = '#000000',\n ## Padding @control range min=0 max=4 step=0.5\n padding = 1,\n ## Alignment @control radio left|center|right\n align = 'left',\n ## Not a number @control range\n label = 'x',\n ## Who knows @control wheel\n other = 1\n%}",
            'panel.html.twig' => '<p></p>',
        ]));

        $this->builder = new ControlsBuilder($extractor, new ComponentIntrospector($extractor));
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

    private function anonymous(string $template = 'card.html.twig'): ComponentMetadata
    {
        return new ComponentMetadata(['key' => 'Card', 'template' => $template]);
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

    public function testTemplateTypesDeclareAndDocumentTheArgs(): void
    {
        $code = "{% types {\n ## The message @control textarea\n message: 'string',\n ## Columns @control range min=1 max=4\n columns?: 'number',\n flag: 'boolean',\n} %}<p>{{ message }}</p>";

        $controls = $this->byName($this->builder->forTemplate(['message' => 'Hi', 'extra' => 1], $code));

        $this->assertSame(['message', 'columns', 'flag', 'extra'], array_keys($controls));
        $this->assertSame('textarea', $controls['message']->type);
        $this->assertSame('Hi', $controls['message']->value);
        $this->assertSame('The message', $controls['message']->description);
        $this->assertSame('types', $controls['message']->source);
        $this->assertTrue($controls['message']->inYaml);

        $this->assertSame('range', $controls['columns']->type);
        $this->assertNull($controls['columns']->value);
        $this->assertSame(1.0, $controls['columns']->min);
        $this->assertFalse($controls['columns']->inYaml);
        $this->assertSame('checkbox', $controls['flag']->type);
        $this->assertSame('yaml', $controls['extra']->source);
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

    public function testDirectivesOfThePropsTag(): void
    {
        self::requirePropDocumentation();

        $controls = $this->byName($this->builder->forComponent($this->anonymous('documented.html.twig'), []));

        $this->assertSame('color', $controls['accent']->type);
        $this->assertSame('Accent', $controls['accent']->description);
        $this->assertSame('range', $controls['padding']->type);
        $this->assertSame([0.0, 4.0, 0.5], [$controls['padding']->min, $controls['padding']->max, $controls['padding']->step]);
        $this->assertSame('radio', $controls['align']->type);
        $this->assertSame(['left', 'center', 'right'], $controls['align']->choices);

        $this->assertSame('text', $controls['label']->type);
        $this->assertTrue($controls['label']->editable);
        $this->assertSame('"range" does not fit the value', $controls['label']->note);
        $this->assertSame('number', $controls['other']->type);
        $this->assertSame('unknown control "wheel"', $controls['other']->note);
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
        $this->assertSame('textarea', $controls['title']->type);
        $this->assertSame('Title of the panel.', $controls['title']->description);
        $this->assertSame(5, $controls['rows']->value);
        $this->assertSame(2, $controls['rows']->default);
        $this->assertSame('radio', $controls['size']->type);
        $this->assertSame('small', $controls['size']->value);
        $this->assertSame('date', $controls['date']->type);
        $this->assertTrue($controls['date']->editable);
        $this->assertNull($controls['required']->default);
    }

    public function testMountParametersOfTheComponentClass(): void
    {
        $metadata = new ComponentMetadata(['key' => 'Toast', 'template' => 'panel.html.twig', 'class' => Toast::class, 'service_id' => 'toast', 'expose_public_props' => false]);

        $controls = $this->byName($this->builder->forComponent($metadata, ['message' => 'Hi']));

        $this->assertSame(['message', 'delay', 'at'], array_keys($controls));
        $this->assertSame('Hi', $controls['message']->value);
        $this->assertSame('range', $controls['delay']->type);
        $this->assertSame(5, $controls['delay']->value);
        $this->assertSame('date', $controls['at']->type);
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
            'date' => '2024-02-29',
        ]);

        $this->assertSame(['title' => 'Hello', 'rows' => 7, 'closable' => true, 'size' => PanelSize::Large, 'items' => ['a' => 1]], array_diff_key($result['overrides'], ['date' => null]));
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['overrides']['date']);
        $this->assertSame('2024-02-29', $result['overrides']['date']->format('Y-m-d'));
        $this->assertSame(['title' => 'Hello', 'rows' => 7, 'closable' => true, 'size' => 'large', 'items' => ['a' => 1], 'date' => '2024-02-29'], $result['display']);

        $current = $this->byName($result['controls']);
        $this->assertSame('Hello', $current['title']->current);
        $this->assertSame('Panel', $current['title']->value);
        $this->assertSame('large', $current['size']->current);
        $this->assertSame('2024-02-29', $current['date']->current);
    }

    public function testDirectiveOverridesAreBoundedAndTyped(): void
    {
        self::requirePropDocumentation();

        $controls = $this->builder->forComponent($this->anonymous('documented.html.twig'), []);

        $result = $this->builder->applyOverrides($controls, ['accent' => '#ABCDEF', 'padding' => '2.5', 'align' => 'center']);
        $this->assertSame(['accent' => '#abcdef', 'padding' => 2.5, 'align' => 'center'], $result['overrides']);

        $result = $this->builder->applyOverrides($controls, ['accent' => 'red', 'padding' => 5, 'align' => 'top']);
        $this->assertSame([], $result['overrides']);
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
            'date' => '2024-02-30',
            0 => 'numeric key',
        ]);

        $this->assertSame([], $result['overrides']);
        $this->assertSame([], $result['display']);
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
