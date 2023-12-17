<?php

/*
 * This file is part of the Arqui project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\ArquiBundle\Dto;

class Menu
{
    public function __construct(
        private ?string $label = null,
        private array $children = [],
    ) {
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return Menu[]|MenuItem[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(self|MenuItem $child): static
    {
        $this->children[] = $child;

        return $this;
    }
}
