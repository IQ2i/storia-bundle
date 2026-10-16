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
        yield 'color' => ['#1A2b3C', 'color', true];
        yield 'short hex is a text' => ['#abc', 'text', true];
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

    public function testLargeArraysAreReadOnly(): void
    {
        $control = ControlFactory::fromValue('data', array_fill(0, 300, 'some value'), 'yaml');

        $this->assertSame('json', $control->type);
        $this->assertFalse($control->editable);
        $this->assertSame('too large', $control->note);
    }

    public function testDateValuesAreDisplayedAsDays(): void
    {
        $control = ControlFactory::fromValue('day', new \DateTimeImmutable('2024-05-06 10:00'), 'yaml');

        $this->assertSame('text', $control->type);
        $this->assertSame('2024-05-06', $control->value);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function propertyProvider(): iterable
    {
        yield 'string' => ['title', 'text', true];
        yield 'int' => ['rows', 'number', true];
        yield 'bool' => ['closable', 'checkbox', true];
        yield 'small enum' => ['size', 'radio', true];
        yield 'array' => ['items', 'json', true];
        yield 'date' => ['date', 'date', true];
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

    public function testEnumChoicesAreItsCases(): void
    {
        $control = ControlFactory::fromReflectionType('size', (new \ReflectionProperty(Panel::class, 'size'))->getType(), PanelSize::Small, 'class', PanelSize::Small);

        $this->assertSame(['small', 'large'], $control->choices);
        $this->assertSame(PanelSize::class, $control->valueClass);
        $this->assertSame('small', $control->value);
        $this->assertSame('small', $control->default);
    }

    public function testLargeEnumBecomesASelect(): void
    {
        $control = ControlFactory::fromReflectionType('day', (new \ReflectionProperty(WeekDayHolder::class, 'day'))->getType(), WeekDay::Monday, 'class');

        $this->assertSame('select', $control->type);
        $this->assertCount(7, $control->choices);
    }

    public function testDateClassIsKept(): void
    {
        $control = ControlFactory::fromReflectionType('date', (new \ReflectionProperty(Panel::class, 'date'))->getType(), new \DateTime('2024-01-02'), 'class');

        $this->assertSame('date', $control->type);
        $this->assertSame(\DateTimeImmutable::class, $control->valueClass);
        $this->assertSame('2024-01-02', $control->value);

        $control = ControlFactory::fromReflectionType('at', (new \ReflectionProperty(WeekDayHolder::class, 'at'))->getType(), null, 'class');
        $this->assertSame(\DateTime::class, $control->valueClass);
    }

    public function testUnsupportedObjectIsReadOnly(): void
    {
        $control = ControlFactory::fromReflectionType('holder', (new \ReflectionProperty(WeekDayHolder::class, 'holder'))->getType(), null, 'class');

        $this->assertSame('json', $control->type);
        $this->assertFalse($control->editable);
        $this->assertSame('unsupported type', $control->note);
    }

    public function testUntypedPropertyFallsBackToTheValue(): void
    {
        $this->assertSame('number', ControlFactory::fromReflectionType('x', null, 2, 'class')->type);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function twigTypeProvider(): iterable
    {
        yield 'string' => ['string', 'text', true];
        yield 'number' => ['number', 'number', true];
        yield 'boolean' => ['boolean', 'checkbox', true];
        yield 'array' => ['string[]', 'json', true];
        yield 'iterable' => ['iterable', 'json', true];
        yield 'object' => ['\App\Entity\User', 'json', false];
    }

    #[DataProvider('twigTypeProvider')]
    public function testInferenceFromTwigType(string $twigType, string $type, bool $editable): void
    {
        $control = ControlFactory::fromTwigType('arg', $twigType, null, 'types');

        $this->assertSame($type, $control->type);
        $this->assertSame($editable, $control->editable);
        $this->assertSame('types', $control->source);
    }

    public function testDirectiveDescriptionIsAlwaysKept(): void
    {
        $control = ControlFactory::withDirective(ControlFactory::fromValue('n', 1, 'yaml'), ControlDirective::parse('A number'));

        $this->assertSame('A number', $control->description);
        $this->assertSame('number', $control->type);
        $this->assertNull(ControlFactory::withDirective($control, null)->note);
    }

    /**
     * @return iterable<string, array{mixed, string, string, ?string}>
     */
    public static function directiveProvider(): iterable
    {
        yield 'color on a string' => ['red', '@control color', 'color', null];
        yield 'color on null' => [null, '@control color', 'color', null];
        yield 'textarea on a string' => ['x', '@control textarea', 'textarea', null];
        yield 'date on a string' => ['2024-01-01', '@control date', 'date', null];
        yield 'range on a number' => [1, '@control range min=0 max=2', 'range', null];
        yield 'number on a number' => [1.5, '@control number', 'number', null];
        yield 'select on a string with choices' => ['a', '@control select a|b', 'select', null];
        yield 'radio on a string with choices' => ['a', '@control radio a|b', 'radio', null];
        yield 'checkbox on a bool' => [true, '@control checkbox', 'checkbox', null];
        yield 'range on a string' => ['x', '@control range', 'text', '"range" does not fit the value'];
        yield 'color on a number' => [1, '@control color', 'number', '"color" does not fit the value'];
        yield 'select without choices on a string' => ['x', '@control select', 'text', '"select" does not fit the value'];
        yield 'checkbox on a string' => ['x', '@control checkbox', 'text', '"checkbox" does not fit the value'];
        yield 'unknown' => ['x', '@control wheel', 'text', 'unknown control "wheel"'];
    }

    #[DataProvider('directiveProvider')]
    public function testDirectiveMustFitTheValue(mixed $value, string $documentation, string $type, ?string $note): void
    {
        $control = ControlFactory::withDirective(ControlFactory::fromValue('arg', $value, 'props'), ControlDirective::parse($documentation));

        $this->assertSame($type, $control->type);
        $this->assertSame($note, $control->note);
        $this->assertTrue($control->editable);
    }

    public function testRangeDefaults(): void
    {
        $control = ControlFactory::withDirective(ControlFactory::fromValue('n', 1, 'props'), ControlDirective::parse('@control range'));

        $this->assertSame([0.0, 100.0, 1.0], [$control->min, $control->max, $control->step]);
    }

    public function testEnumDirectiveOnlyPicksTheWidget(): void
    {
        $control = ControlFactory::fromReflectionType('size', (new \ReflectionProperty(Panel::class, 'size'))->getType(), PanelSize::Small, 'class');

        $select = ControlFactory::withDirective($control, ControlDirective::parse('@control select x|y'));

        $this->assertSame('select', $select->type);
        $this->assertSame(['small', 'large'], $select->choices);
        $this->assertSame(PanelSize::class, $select->valueClass);
    }

    public function testReadOnlyControlsIgnoreTheDirectiveType(): void
    {
        $control = ControlFactory::withDirective(ControlFactory::fromValue('o', new \stdClass(), 'props'), ControlDirective::parse('Object @control text'));

        $this->assertSame('json', $control->type);
        $this->assertSame('Object', $control->description);
        $this->assertFalse($control->editable);
    }
}

enum WeekDay: string
{
    case Monday = 'mon';
    case Tuesday = 'tue';
    case Wednesday = 'wed';
    case Thursday = 'thu';
    case Friday = 'fri';
    case Saturday = 'sat';
    case Sunday = 'sun';
}

final class WeekDayHolder
{
    public WeekDay $day = WeekDay::Monday;

    public ?\DateTime $at = null;

    public ?self $holder = null;
}
