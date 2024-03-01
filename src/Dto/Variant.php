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

class Variant implements \Stringable
{
    private ?string $includeContent = null;

    private ?string $twigContent = null;

    private ?string $htmlContent = null;

    private ?string $markdownContent = null;

    public function __construct(
        private readonly string $path,
        private readonly string $name,
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

    public function getIncludeContent(): ?string
    {
        return $this->includeContent;
    }

    public function setIncludeContent(?string $includeContent): static
    {
        $this->includeContent = $includeContent;

        return $this;
    }

    public function getTwigContent(): ?string
    {
        return $this->twigContent;
    }

    public function setTwigContent(?string $twigContent): static
    {
        $this->twigContent = $twigContent;

        return $this;
    }

    public function getHtmlContent(): ?string
    {
        return $this->htmlContent;
    }

    public function setHtmlContent(?string $htmlContent): static
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    public function getMarkdownContent(): ?string
    {
        return $this->markdownContent;
    }

    public function setMarkdownContent(?string $markdownContent): static
    {
        $this->markdownContent = $markdownContent;

        return $this;
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
