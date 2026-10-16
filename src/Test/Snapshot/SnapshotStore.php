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

namespace IQ2i\StoriaBundle\Test\Snapshot;

use IQ2i\StoriaBundle\Test\VariantRunner;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

readonly class SnapshotStore
{
    public function __construct(
        private string $snapshotPath,
        private Filesystem $filesystem,
    ) {
    }

    public function withPath(string $snapshotPath): self
    {
        return new self($snapshotPath, $this->filesystem);
    }

    public function path(string $view, string $variant): string
    {
        foreach (explode('/', $view) as $segment) {
            if ('' === $segment || '.' === $segment || '..' === $segment || str_contains($segment, '\\') || str_contains($segment, "\0")) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a valid view path for a snapshot.', $view));
            }
        }

        if (!preg_match(VariantRunner::SAFE_NAME, $variant)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid variant name for a snapshot.', $variant));
        }

        return rtrim($this->snapshotPath, '/').'/'.$view.'/'.$variant.'.html';
    }

    public function exists(string $view, string $variant): bool
    {
        return is_file($this->path($view, $variant));
    }

    public function read(string $view, string $variant): string
    {
        $content = @file_get_contents($this->path($view, $variant));
        if (false === $content) {
            throw new \RuntimeException(\sprintf('Snapshot of "%s:%s" cannot be read.', $view, $variant));
        }

        return $content;
    }

    public function write(string $view, string $variant, string $html): void
    {
        $this->filesystem->dumpFile($this->path($view, $variant), SnapshotNormalizer::normalize($html));
    }

    /**
     * @return list<string> the "view:variant" key of every stored snapshot
     */
    public function all(): array
    {
        if (!is_dir($this->snapshotPath)) {
            return [];
        }

        $keys = [];
        foreach ((new Finder())->files()->in($this->snapshotPath)->name('*.html') as $file) {
            $relative = substr($file->getRelativePathname(), 0, -\strlen('.html'));
            $keys[] = str_replace(\DIRECTORY_SEPARATOR, '/', \dirname($relative)).':'.basename($relative);
        }

        sort($keys);

        return $keys;
    }
}
