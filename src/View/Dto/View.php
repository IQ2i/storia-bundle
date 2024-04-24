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

namespace IQ2i\StoriaBundle\View\Dto;

class View implements \Stringable
{
    private array $variants = [];

    public function __construct(
        private readonly string $path,
        private readonly string $name,
        private readonly string $template,
        private readonly ?string $currentVariantPath = null,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getCurrentVariantPath(): ?string
    {
        return $this->currentVariantPath;
    }

    public function getCurrentVariant(): ?Variant
    {
        $variant = current(array_filter($this->variants, fn (Variant $variant): bool => $this->currentVariantPath === $variant->getPath()));

        return $variant ?: null;
    }

    public function getFirstVariant(): ?Variant
    {
        $variant = current($this->variants);

        return $variant ?: null;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function addVariant(Variant $variant): static
    {
        if (!\in_array($variant, $this->variants, true)) {
            $this->variants[] = $variant;
        }

        return $this;
    }

    public function removeVariant(Variant $variant): static
    {
        $key = array_search($variant, $this->variants, true);
        if (false === $key) {
            return $this;
        }

        unset($this->variants[$key]);

        return $this;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
