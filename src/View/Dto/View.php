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

readonly class View
{
    public function __construct(
        private string $path,
        private string $name,
        private string $template,
        private bool $isComponent = false,
        private bool $isPage = false,
        private ?string $twigContent = null,
        private ?string $htmlContent = null,
        private ?string $includeContent = null,
        private ?string $markdownContent = null,
        private array $variants = [],
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

    public function isComponent(): bool
    {
        return $this->isComponent;
    }

    public function isPage(): bool
    {
        return $this->isPage;
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
     * @return Variant[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function getCurrentVariant(): ?Variant
    {
        $variants = array_filter($this->variants, static fn (Variant $variant) => $variant->isCurrent());

        return current($variants);
    }
}
