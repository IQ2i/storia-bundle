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

use Symfony\UX\TwigComponent\ComponentMetadata;

/**
 * @internal
 */
readonly class ControlsBuilder
{
    private const string NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_-]*$/';

    public function __construct(
        private PropsExtractor $propsExtractor,
        private ComponentIntrospector $introspector,
    ) {
    }

    /**
     * @param array<string, mixed> $resolvedArgs
     * @param string|null          $code         the source of the template, whose {% types %} tag declares and documents the args
     *
     * @return list<Control>
     */
    public function forTemplate(array $resolvedArgs, ?string $code = null): array
    {
        $types = null === $code ? [] : $this->propsExtractor->types($code);

        $controls = [];
        foreach ($types as $name => $type) {
            $control = \array_key_exists($name, $resolvedArgs)
                ? ControlFactory::fromValue($name, $resolvedArgs[$name], 'types', null, true)
                : ControlFactory::fromTwigType($name, $type->type, null, 'types');

            $controls[] = ControlFactory::withDirective($control, $type->directive);
        }

        foreach ($resolvedArgs as $name => $value) {
            if (!isset($types[$name])) {
                $controls[] = ControlFactory::fromValue((string) $name, $value, 'yaml', inYaml: true);
            }
        }

        return $controls;
    }

    /**
     * @param array<string, mixed> $resolvedArgs
     *
     * @return list<Control>
     */
    public function forComponent(ComponentMetadata $metadata, array $resolvedArgs): array
    {
        $declared = $this->introspector->props($metadata);

        $controls = [];
        foreach ($declared as $name => $prop) {
            $inYaml = \array_key_exists($name, $resolvedArgs);
            $value = $inYaml ? $resolvedArgs[$name] : $prop->default;

            $control = 'class' === $prop->source
                ? ControlFactory::fromReflectionType($name, $prop->type, $value, $prop->source, $prop->default, $inYaml)
                : ControlFactory::fromValue($name, $value, $prop->source, $prop->default, $inYaml);
            $control = ControlFactory::withDirective($control, $prop->directive);

            $controls[] = $prop->isExpression && !$inYaml ? $control->asReadOnly('expression') : $control;
        }

        foreach ($resolvedArgs as $name => $value) {
            if (!isset($declared[$name])) {
                $controls[] = ControlFactory::fromValue((string) $name, $value, 'yaml', inYaml: true);
            }
        }

        return $controls;
    }

    /**
     * Keeps the overrides that target an editable control, coerced to the type of that control.
     * Everything else is dropped: an override is only ever data, never code.
     *
     * @param list<Control>        $controls
     * @param array<string, mixed> $overrides
     *
     * @return array{controls: list<Control>, overrides: array<string, mixed>, display: array<string, mixed>}
     *                                                                                                        the controls with their current value, the values to render (enums and dates as objects),
     *                                                                                                        and the same values as scalars, to display and put back in the URL
     */
    public function applyOverrides(array $controls, array $overrides): array
    {
        $retained = [];
        $display = [];
        $result = [];
        foreach ($controls as $control) {
            $coerced = \array_key_exists($control->name, $overrides)
                && $control->editable
                && 1 === preg_match(self::NAME_PATTERN, $control->name)
                ? $this->coerce($control, $overrides[$control->name])
                : null;

            if (null === $coerced) {
                $result[] = $control;
                continue;
            }

            [$retained[$control->name], $display[$control->name]] = $coerced;
            $result[] = $control->with(current: $display[$control->name]);
        }

        return ['controls' => $result, 'overrides' => $retained, 'display' => $display];
    }

    /**
     * @return array{0: mixed, 1: mixed}|null the value to render and the value to display, null when invalid
     */
    private function coerce(Control $control, mixed $raw): ?array
    {
        $value = match ($control->type) {
            'checkbox' => \is_bool($raw) ? $raw : null,
            'number' => $this->coerceNumber($raw),
            'range' => $this->coerceRange($control, $raw),
            'text', 'textarea' => \is_scalar($raw) ? (string) $raw : null,
            'color' => \is_string($raw) && preg_match('/^#[0-9a-f]{6}$/i', $raw) ? strtolower($raw) : null,
            'date' => $this->coerceDate($control, $raw),
            'json' => \is_array($raw) ? $raw : null,
            'select', 'radio' => $this->coerceChoice($control, $raw),
            default => null,
        };

        if (null === $value) {
            return null;
        }

        $display = match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
            default => $value,
        };

        return [$value, $display];
    }

    private function coerceNumber(mixed $raw): int|float|null
    {
        return match (true) {
            \is_int($raw), \is_float($raw) => $raw,
            \is_string($raw) && is_numeric($raw) => $raw + 0,
            default => null,
        };
    }

    private function coerceRange(Control $control, mixed $raw): int|float|null
    {
        $value = $this->coerceNumber($raw);

        return null !== $value && $value >= ($control->min ?? -\INF) && $value <= ($control->max ?? \INF) ? $value : null;
    }

    private function coerceDate(Control $control, mixed $raw): \DateTimeInterface|string|null
    {
        if (!\is_string($raw) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (false === $date || $date->format('Y-m-d') !== $raw) {
            return null;
        }

        if (null === $control->valueClass) {
            return $raw;
        }

        return \DateTime::class === $control->valueClass ? \DateTime::createFromImmutable($date) : $date;
    }

    private function coerceChoice(Control $control, mixed $raw): mixed
    {
        if (!\is_scalar($raw)) {
            return null;
        }

        foreach ($control->choices as $choice) {
            if ((string) $choice === (string) $raw) {
                return null !== $control->valueClass ? $control->valueClass::from($choice) : $choice;
            }
        }

        return null;
    }
}
