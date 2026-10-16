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
 * A variable declared by the {% types %} tag of a template.
 */
final readonly class TypeDefinition
{
    public function __construct(
        public string $type,
        public bool $optional = false,
        public ?ControlDirective $directive = null,
    ) {
    }
}
