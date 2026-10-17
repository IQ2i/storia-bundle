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
 * A prop declared by a component: in its {% props %} tag, as a public property or as a mount() parameter.
 *
 * @internal
 */
final readonly class PropDefinition
{
    /**
     * @param string $source "props" for the {% props %} tag, "class" for the PHP class
     */
    public function __construct(
        public string $source = 'props',
        public mixed $default = null,
        public bool $hasDefault = false,
        public bool $isExpression = false,
        public ?\ReflectionType $type = null,
        public ?ControlDirective $directive = null,
    ) {
    }
}
