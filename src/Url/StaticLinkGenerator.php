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

namespace IQ2i\StoriaBundle\Url;

use IQ2i\StoriaBundle\Export\UrlRewriter;

/**
 * @internal
 */
readonly class StaticLinkGenerator implements LinkGeneratorInterface
{
    private string $prefix;

    public function __construct(string $currentFile = 'index.html')
    {
        $this->prefix = UrlRewriter::prefixFor($currentFile);
    }

    public function view(string $path, ?string $variant = null): string
    {
        if (null === $variant) {
            throw new \LogicException(\sprintf('View "%s" has no variant, it cannot be exported.', $path));
        }

        return $this->prefix.$this->encode($path.'/'.$variant).'.html';
    }

    public function iframe(string $path, ?string $variant = null): string
    {
        if (null === $variant) {
            throw new \LogicException(\sprintf('View "%s" has no variant, it cannot be exported.', $path));
        }

        return $this->prefix.'iframe/'.$this->encode($path.'/'.$variant).'.html';
    }

    public function asset(string $path): string
    {
        return $this->prefix.'bundles/iq2istoria/'.$path;
    }

    private function encode(string $path): string
    {
        return implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }
}
