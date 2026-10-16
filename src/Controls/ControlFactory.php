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

namespace IQ2i\StoriaBundle\Controls;

/**
 * @internal
 */
final class ControlFactory
{
    /** Above this JSON size an array is displayed but not edited: it is data, not a prop to play with. */
    public const int MAX_JSON_LENGTH = 2048;

    private const int MAX_RADIO_CHOICES = 4;

    private const string COLOR_PATTERN = '/^#[0-9a-f]{6}$/i';

    private const array STRING_TYPES = ['text', 'textarea', 'color', 'date'];

    private const array NUMBER_TYPES = ['number', 'range'];

    private const array CHOICE_TYPES = ['select', 'radio'];

    public static function fromValue(string $name, mixed $value, string $source, mixed $default = null, bool $inYaml = false): Control
    {
        $value = self::normalize($value);
        $default = self::normalize($default);

        if (\is_array($value)) {
            return self::json($name, $value, $source, $default, $inYaml);
        }

        if (\is_object($value)) {
            return (new Control($name, 'json', $value, $value, $default, $source, false, $inYaml))->asReadOnly('object');
        }

        $type = match (true) {
            \is_bool($value) => 'checkbox',
            \is_int($value), \is_float($value) => 'number',
            \is_string($value) => self::stringType($value),
            default => 'text',
        };

        return new Control($name, $type, $value, $value, $default, $source, true, $inYaml);
    }

    public static function fromReflectionType(string $name, ?\ReflectionType $type, mixed $value, string $source, mixed $default = null, bool $inYaml = false): Control
    {
        if (!$type instanceof \ReflectionNamedType) {
            return null === $type
                ? self::fromValue($name, $value, $source, $default, $inYaml)
                : self::json($name, $value, $source, $default, $inYaml)->asReadOnly('unsupported type');
        }

        $typeName = $type->getName();
        $value = self::normalize($value);
        $default = self::normalize($default);

        if (is_a($typeName, \BackedEnum::class, true)) {
            $choices = array_map(static fn (\BackedEnum $case): int|string => $case->value, $typeName::cases());

            return new Control($name, \count($choices) <= self::MAX_RADIO_CHOICES ? 'radio' : 'select', $value, $value, $default, $source, true, $inYaml, $choices, $typeName);
        }

        if (is_a($typeName, \DateTimeInterface::class, true)) {
            $valueClass = \DateTimeInterface::class === $typeName ? \DateTimeImmutable::class : $typeName;

            return new Control($name, 'date', $value, $value, $default, $source, true, $inYaml, [], $valueClass);
        }

        return match ($typeName) {
            'bool' => new Control($name, 'checkbox', $value, $value, $default, $source, true, $inYaml),
            'int', 'float' => new Control($name, 'number', $value, $value, $default, $source, true, $inYaml),
            'string' => new Control($name, \is_string($value) ? self::stringType($value) : 'text', $value, $value, $default, $source, true, $inYaml),
            'array' => self::json($name, $value, $source, $default, $inYaml),
            'mixed' => self::fromValue($name, $value, $source, $default, $inYaml),
            default => self::json($name, $value, $source, $default, $inYaml)->asReadOnly('unsupported type'),
        };
    }

    /**
     * @param string $twigType a type of the {% types %} tag, e.g. "string", "number", "boolean" or "string[]"
     */
    public static function fromTwigType(string $name, string $twigType, mixed $value, string $source, mixed $default = null, bool $inYaml = false): Control
    {
        $value = self::normalize($value);
        $twigType = strtolower(trim($twigType));

        if (str_ends_with($twigType, '[]') || str_starts_with($twigType, 'array<') || \in_array($twigType, ['array', 'iterable', 'list'], true)) {
            return self::json($name, $value, $source, $default, $inYaml);
        }

        return match ($twigType) {
            'string' => new Control($name, \is_string($value) ? self::stringType($value) : 'text', $value, $value, $default, $source, true, $inYaml),
            'number', 'int', 'integer', 'float' => new Control($name, 'number', $value, $value, $default, $source, true, $inYaml),
            'boolean', 'bool' => new Control($name, 'checkbox', $value, $value, $default, $source, true, $inYaml),
            'mixed' => self::fromValue($name, $value, $source, $default, $inYaml),
            default => (new Control($name, 'json', $value, $value, $default, $source, false, $inYaml))->asReadOnly('unsupported type'),
        };
    }

    /**
     * Applies the "@control" directive of the prop documentation. A directive that does not fit the value
     * (e.g. a range on a string) is ignored, and the control says so.
     */
    public static function withDirective(Control $control, ?ControlDirective $directive): Control
    {
        if (null === $directive) {
            return $control;
        }

        $control = $control->with(description: $directive->description);
        if (null === $directive->type || !$control->editable) {
            return $control;
        }

        $type = $directive->type;
        $isString = \in_array($control->type, self::STRING_TYPES, true) || null === $control->value;
        $fits = match ($type) {
            'text', 'textarea', 'color', 'date' => $isString,
            'number', 'range' => \in_array($control->type, self::NUMBER_TYPES, true) || null === $control->value,
            'select', 'radio' => \in_array($control->type, self::CHOICE_TYPES, true) || ($isString && [] !== $directive->choices),
            'checkbox', 'json' => $type === $control->type,
            default => null,
        };

        if (null === $fits) {
            return $control->with(note: \sprintf('unknown control "%s"', $type));
        }

        if (!$fits) {
            return $control->with(note: \sprintf('"%s" does not fit the value', $type));
        }

        return match ($type) {
            'range' => $control->with(type: 'range', min: $directive->number('min') ?? 0.0, max: $directive->number('max') ?? 100.0, step: $directive->number('step') ?? 1.0),
            // The choices of an enum are its cases, the directive can only pick the widget
            'select', 'radio' => $control->with(type: $type, choices: null !== $control->valueClass ? $control->choices : $directive->choices),
            default => $control->with(type: $type),
        };
    }

    private static function stringType(string $value): string
    {
        return match (true) {
            1 === preg_match(self::COLOR_PATTERN, $value) => 'color',
            str_contains($value, "\n") || mb_strlen($value) > 80 => 'textarea',
            default => 'text',
        };
    }

    private static function json(string $name, mixed $value, string $source, mixed $default, bool $inYaml): Control
    {
        $value = self::normalize($value);
        $control = new Control($name, 'json', $value, $value, self::normalize($default), $source, true, $inYaml);

        return \is_array($value) && \strlen((string) json_encode($value)) > self::MAX_JSON_LENGTH ? $control->asReadOnly('too large') : $control;
    }

    private static function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            default => $value,
        };
    }
}
