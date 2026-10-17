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
 * The documentation of a prop: a free description, optionally followed by "@control <type> [key=value ...] [a|b|c]".
 *
 *   ## Padding in rem @control range min=0 max=4 step=0.5
 *   ## Alignment @control radio left|center|right
 *
 * @internal
 */
final readonly class ControlDirective
{
    /**
     * @param array<string, float|string> $options
     * @param list<string>                $choices
     */
    public function __construct(
        public ?string $description = null,
        public ?string $type = null,
        public array $options = [],
        public array $choices = [],
    ) {
    }

    public static function parse(?string $documentation): ?self
    {
        if (null === $documentation) {
            return null;
        }

        // A PHP docblock keeps its markers, a Twig "##" comment does not
        $text = preg_replace(['#^/\*\*|\*/$#', '/^\s*\*\s?/m'], '', trim($documentation));
        $text = trim((string) preg_replace('/\s+/', ' ', (string) $text));
        if ('' === $text) {
            return null;
        }

        if (!preg_match('/^(.*?)\s*@control\b\s*(.*)$/', $text, $matches)) {
            return new self($text);
        }

        $description = '' !== $matches[1] ? $matches[1] : null;
        $words = preg_split('/\s+/', trim($matches[2]), -1, \PREG_SPLIT_NO_EMPTY) ?: [];
        if ([] === $words) {
            return new self($description);
        }

        $type = strtolower(array_shift($words));
        $options = [];
        $choices = [];
        foreach ($words as $word) {
            if (preg_match('/^([a-z]+)=(.+)$/i', $word, $option)) {
                $options[strtolower($option[1])] = is_numeric($option[2]) ? (float) $option[2] : $option[2];
            } elseif ([] === $choices) {
                $choices = array_values(array_filter(explode('|', $word), static fn (string $choice): bool => '' !== $choice));
            }
        }

        return new self($description, $type, $options, $choices);
    }

    public function number(string $option): ?float
    {
        $value = $this->options[$option] ?? null;

        return \is_float($value) ? $value : null;
    }
}
