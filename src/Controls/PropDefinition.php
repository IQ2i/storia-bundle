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

final readonly class PropDefinition
{
    public function __construct(
        public mixed $default = null,
        public bool $hasDefault = false,
        public bool $isExpression = false,
    ) {
    }
}
