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

namespace IQ2i\StoriaBundle\Util;

/**
 * Writes a PHP value as a Twig expression, for the Include tab: the result is only displayed, never compiled.
 *
 * @internal
 */
final class TwigLiteral
{
    public static function export(mixed $value): string
    {
        return match (true) {
            null === $value => 'null',
            \is_bool($value) => $value ? 'true' : 'false',
            \is_int($value) => (string) $value,
            \is_float($value) => is_finite($value) ? self::exportFloat($value) : 'null',
            \is_string($value) => self::exportString($value),
            $value instanceof \BackedEnum, $value instanceof \UnitEnum => \sprintf("constant('%s')", self::escape($value::class.'::'.$value->name)),
            $value instanceof \DateTimeInterface => \sprintf("date('%s')", $value->format(\DATE_ATOM)),
            \is_array($value) => self::exportArray($value),
            \is_object($value) => self::exportString(\sprintf('(%s object)', $value::class)),
            default => 'null',
        };
    }

    private static function exportFloat(float $value): string
    {
        $literal = json_encode($value, \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR);

        return str_contains($literal, '.') || str_contains($literal, 'e') ? $literal : $literal.'.0';
    }

    private static function exportString(string $value): string
    {
        return "'".self::escape($value)."'";
    }

    // The double quote becomes an escape sequence so that the literal can sit in an HTML attribute
    private static function escape(string $value): string
    {
        return str_replace(['\\', "'", '"', "\n", "\r", "\t"], ['\\\\', "\\'", '\x22', '\n', '\r', '\t'], $value);
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private static function exportArray(array $value): string
    {
        if (array_is_list($value)) {
            return '['.implode(', ', array_map(self::export(...), $value)).']';
        }

        $items = [];
        foreach ($value as $key => $item) {
            $items[] = (\is_int($key) ? (string) $key : self::exportString($key)).': '.self::export($item);
        }

        return '{'.implode(', ', $items).'}';
    }
}
