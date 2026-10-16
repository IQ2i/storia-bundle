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

readonly class ControlsBuilder
{
    private const string NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_-]*$/';

    public function __construct(
        private PropsExtractor $propsExtractor,
    ) {
    }

    /**
     * @param array<string, mixed> $resolvedArgs
     *
     * @return list<Control>
     */
    public function forTemplate(array $resolvedArgs): array
    {
        $controls = [];
        foreach ($resolvedArgs as $name => $value) {
            $controls[] = ControlFactory::fromValue((string) $name, $value, 'yaml', inYaml: true);
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
        $declared = [];
        foreach ($this->propsExtractor->extract($metadata->getTemplate()) as $name => $prop) {
            $declared[$name] = ['props', $prop, null];
        }

        if (!$metadata->isAnonymous() && $metadata->isPublicPropsExposed()) {
            $properties = (new \ReflectionClass($metadata->getClass()))->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($properties as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                $default = new PropDefinition($property->hasDefaultValue() ? $property->getDefaultValue() : null, $property->hasDefaultValue());
                $declared[$property->getName()] = ['class', $default, $property->getType()];
            }
        }

        $controls = [];
        foreach ($declared as $name => [$source, $prop, $type]) {
            $inYaml = \array_key_exists($name, $resolvedArgs);
            $value = $inYaml ? $resolvedArgs[$name] : $prop->default;

            $control = 'class' === $source
                ? ControlFactory::fromReflectionType($name, $type, $value, $source, $prop->default, $inYaml)
                : ControlFactory::fromValue($name, $value, $source, $prop->default, $inYaml);

            $controls[] = $prop->isExpression && !$inYaml ? $control->asReadOnly() : $control;
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
     * @return array{controls: list<Control>, overrides: array<string, mixed>}
     */
    public function applyOverrides(array $controls, array $overrides): array
    {
        $retained = [];
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

            [$retained[$control->name], $display] = $coerced;
            $result[] = $control->withCurrent($display);
        }

        return ['controls' => $result, 'overrides' => $retained];
    }

    /**
     * @return array{0: mixed, 1: mixed}|null the value to render and the value to display, null when invalid
     */
    private function coerce(Control $control, mixed $raw): ?array
    {
        $value = match ($control->type) {
            'checkbox' => \is_bool($raw) ? $raw : null,
            'number' => match (true) {
                \is_int($raw), \is_float($raw) => $raw,
                \is_string($raw) && is_numeric($raw) => $raw + 0,
                default => null,
            },
            'text', 'textarea' => \is_scalar($raw) ? (string) $raw : null,
            'json' => \is_array($raw) ? $raw : null,
            'select' => $this->coerceChoice($control, $raw),
            default => null,
        };

        if (null === $value) {
            return null;
        }

        return [$value, $value instanceof \BackedEnum ? $value->value : $value];
    }

    private function coerceChoice(Control $control, mixed $raw): mixed
    {
        if (!\is_scalar($raw)) {
            return null;
        }

        foreach ($control->choices as $choice) {
            if ((string) $choice === (string) $raw) {
                return null !== $control->enumClass ? $control->enumClass::from($choice) : $choice;
            }
        }

        return null;
    }
}
