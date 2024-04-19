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

namespace IQ2i\StoriaBundle\Menu\Dto;

class Menu
{
    public function __construct(
        private readonly ?string $label = null,
        private bool $opened = false,
        private array $children = [],
    ) {
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function isOpened(): bool
    {
        return $this->opened;
    }

    public function setOpened(bool $opened): static
    {
        $this->opened = $opened;

        return $this;
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

    public function reorderChildren(): static
    {
        usort($this->children, static fn (Menu|MenuItem $itemA, Menu|MenuItem $itemB): int => strcmp((string) $itemA->getLabel(), (string) $itemB->getLabel()));

        return $this;
    }
}
