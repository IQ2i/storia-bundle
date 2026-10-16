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

final readonly class Control
{
    public const array TYPES = ['text', 'textarea', 'number', 'checkbox', 'select', 'json'];

    /**
     * @param string       $source  Where the control comes from: "props", "class" or "yaml"
     * @param list<scalar> $choices Allowed values of a "select" control
     */
    public function __construct(
        public string $name,
        public string $type,
        public mixed $value,
        public mixed $current,
        public mixed $default,
        public string $source,
        public bool $editable = true,
        public bool $inYaml = false,
        public array $choices = [],
        public ?string $enumClass = null,
    ) {
    }

    public function withCurrent(mixed $current): self
    {
        return new self(...[...get_object_vars($this), 'current' => $current]);
    }

    public function asReadOnly(): self
    {
        return new self(...[...get_object_vars($this), 'editable' => false]);
    }
}
