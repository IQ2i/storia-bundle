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

class Component implements \Stringable
{
    public function __construct(
        private readonly string $path,
        private readonly string $name,
        private ?Variant $variant = null,
        private ?string $iframeContent = null,
        private ?string $twigContent = null,
        private ?string $htmlContent = null,
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

    public function getVariant(): ?Variant
    {
        return $this->variant;
    }

    public function setVariant(?Variant $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getIframeContent(): ?string
    {
        return $this->iframeContent;
    }

    public function setIframeContent(?string $iframeContent): static
    {
        $this->iframeContent = $iframeContent;

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

    public function __toString(): string
    {
        return $this->path;
    }
}
