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

use IQ2i\StoriaBundle\Controls\Control;

/**
 * @internal
 */
readonly class View
{
    /**
     * @param list<Variant>        $variants
     * @param list<Control>        $controls
     * @param array<string, mixed> $overrides
     */
    public function __construct(
        private string $path,
        private ?string $twigContent = null,
        private ?string $htmlContent = null,
        private ?string $includeContent = null,
        private ?string $markdownContent = null,
        private array $variants = [],
        private array $controls = [],
        private array $overrides = [],
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTwigContent(): ?string
    {
        return $this->twigContent;
    }

    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }

    public function getIncludeContent(): ?string
    {
        return $this->includeContent;
    }

    public function getMarkdownContent(): ?string
    {
        return $this->markdownContent;
    }

    /**
     * @return list<Variant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @return list<Control>
     */
    public function getControls(): array
    {
        return $this->controls;
    }

    /**
     * @return array<string, mixed> the overrides applied to the args of the current variant
     */
    public function getOverrides(): array
    {
        return $this->overrides;
    }

    public function getCurrentVariant(): ?Variant
    {
        foreach ($this->variants as $variant) {
            if ($variant->isCurrent()) {
                return $variant;
            }
        }

        return null;
    }
}
