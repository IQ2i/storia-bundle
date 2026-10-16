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
    /**
     * @param string       $source     Where the control comes from: "props", "class", "types" or "yaml"
     * @param list<scalar> $choices    Allowed values of a "select" or "radio" control
     * @param string|null  $valueClass The enum or date class the rendered value must be an instance of
     * @param string|null  $note       Why a control is read-only, or why its directive was ignored
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
        public ?string $valueClass = null,
        public ?string $note = null,
        public ?string $description = null,
        public ?float $min = null,
        public ?float $max = null,
        public ?float $step = null,
    ) {
    }

    public function with(mixed ...$changes): self
    {
        return new self(...[...get_object_vars($this), ...$changes]);
    }

    public function asReadOnly(string $note): self
    {
        return $this->with(editable: false, note: $note);
    }
}
