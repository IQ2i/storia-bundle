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

final class ControlFactory
{
    public static function fromValue(string $name, mixed $value, string $source, mixed $default = null, bool $inYaml = false): Control
    {
        $value = self::normalize($value);
        $default = self::normalize($default);

        $type = match (true) {
            \is_bool($value) => 'checkbox',
            \is_int($value), \is_float($value) => 'number',
            \is_string($value) => str_contains($value, "\n") || mb_strlen($value) > 80 ? 'textarea' : 'text',
            \is_array($value), \is_object($value) => 'json',
            default => 'text',
        };

        return new Control($name, $type, $value, $value, $default, $source, !\is_object($value), $inYaml);
    }

    public static function fromReflectionType(string $name, ?\ReflectionType $type, mixed $value, string $source, mixed $default = null, bool $inYaml = false): Control
    {
        if (!$type instanceof \ReflectionNamedType) {
            return null === $type
                ? self::fromValue($name, $value, $source, $default, $inYaml)
                : self::json($name, $value, $source, $default, $inYaml, false);
        }

        $typeName = $type->getName();
        $value = self::normalize($value);
        $default = self::normalize($default);

        if (is_a($typeName, \BackedEnum::class, true)) {
            $choices = array_map(static fn (\BackedEnum $case): int|string => $case->value, $typeName::cases());

            return new Control($name, 'select', $value, $value, $default, $source, true, $inYaml, $choices, $typeName);
        }

        return match ($typeName) {
            'bool' => new Control($name, 'checkbox', $value, $value, $default, $source, true, $inYaml),
            'int', 'float' => new Control($name, 'number', $value, $value, $default, $source, true, $inYaml),
            'string' => new Control($name, \is_string($value) && (str_contains($value, "\n") || mb_strlen($value) > 80) ? 'textarea' : 'text', $value, $value, $default, $source, true, $inYaml),
            'array' => self::json($name, $value, $source, $default, $inYaml, true),
            'mixed' => self::fromValue($name, $value, $source, $default, $inYaml),
            default => self::json($name, $value, $source, $default, $inYaml, false),
        };
    }

    private static function json(string $name, mixed $value, string $source, mixed $default, bool $inYaml, bool $editable): Control
    {
        $value = self::normalize($value);

        return new Control($name, 'json', $value, $value, self::normalize($default), $source, $editable, $inYaml);
    }

    private static function normalize(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
