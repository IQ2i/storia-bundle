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

use IQ2i\StoriaBundle\Controls\ControlFactory;
use IQ2i\StoriaBundle\Tests\Fixtures\Panel;
use IQ2i\StoriaBundle\Tests\Fixtures\PanelSize;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ControlFactoryTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string, bool}>
     */
    public static function valueProvider(): iterable
    {
        yield 'bool' => [true, 'checkbox', true];
        yield 'int' => [3, 'number', true];
        yield 'float' => [1.5, 'number', true];
        yield 'string' => ['hello', 'text', true];
        yield 'multiline string' => ["a\nb", 'textarea', true];
        yield 'long string' => [str_repeat('a', 81), 'textarea', true];
        yield 'null' => [null, 'text', true];
        yield 'array' => [['a' => 1], 'json', true];
        yield 'object' => [new \stdClass(), 'json', false];
    }

    #[DataProvider('valueProvider')]
    public function testInferenceFromValue(mixed $value, string $type, bool $editable): void
    {
        $control = ControlFactory::fromValue('arg', $value, 'yaml', 'default', true);

        $this->assertSame($type, $control->type);
        $this->assertSame($editable, $control->editable);
        $this->assertSame('arg', $control->name);
        $this->assertSame('yaml', $control->source);
        $this->assertSame('default', $control->default);
        $this->assertSame($value, $control->value);
        $this->assertSame($value, $control->current);
        $this->assertTrue($control->inYaml);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function propertyProvider(): iterable
    {
        yield 'string' => ['title', 'text', true];
        yield 'int' => ['rows', 'number', true];
        yield 'bool' => ['closable', 'checkbox', true];
        yield 'enum' => ['size', 'select', true];
        yield 'array' => ['items', 'json', true];
        yield 'object' => ['date', 'json', false];
    }

    #[DataProvider('propertyProvider')]
    public function testInferenceFromReflectionType(string $property, string $type, bool $editable): void
    {
        $reflection = new \ReflectionProperty(Panel::class, $property);

        $control = ControlFactory::fromReflectionType($property, $reflection->getType(), $reflection->getDefaultValue(), 'class');

        $this->assertSame($type, $control->type);
        $this->assertSame($editable, $control->editable);
        $this->assertSame('class', $control->source);
    }

    public function testEnumBecomesASelect(): void
    {
        $control = ControlFactory::fromReflectionType('size', (new \ReflectionProperty(Panel::class, 'size'))->getType(), PanelSize::Small, 'class', PanelSize::Small);

        $this->assertSame(['small', 'large'], $control->choices);
        $this->assertSame(PanelSize::class, $control->enumClass);
        $this->assertSame('small', $control->value);
        $this->assertSame('small', $control->default);
    }

    public function testUntypedPropertyFallsBackToTheValue(): void
    {
        $this->assertSame('number', ControlFactory::fromReflectionType('x', null, 2, 'class')->type);
    }
}
