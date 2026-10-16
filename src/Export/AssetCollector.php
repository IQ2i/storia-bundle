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

namespace IQ2i\StoriaBundle\Export;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

readonly class AssetCollector
{
    private const string BUNDLE_URL_PREFIX = '/bundles/iq2istoria/';

    private const array FORBIDDEN_EXTENSIONS = ['php', 'phtml', 'phar'];

    /**
     * @param list<string> $globs
     */
    public function __construct(
        private string $publicDir,
        private array $globs,
        private HttpKernelInterface $httpKernel,
        private UrlRewriter $urlRewriter,
        private Filesystem $filesystem,
        private ?Profiler $profiler = null,
    ) {
    }

    /**
     * @param array<string, list<string>> $references
     */
    public function collect(array $references, string $outputDir): int
    {
        $this->profiler?->disable();

        /** @var array<string, string> $queue URL path => referencing page */
        $queue = [];
        foreach ($references as $source => $urls) {
            foreach ($urls as $url) {
                $queue += [$this->pathOf($url) => $source];
            }
        }

        foreach ($this->findGlobbedFiles() as $path) {
            $queue += ['/'.$path => 'export.assets'];
        }

        $exported = 0;
        $done = [];
        while ([] !== $queue) {
            $source = reset($queue);
            $path = (string) key($queue);
            unset($queue[$path]);

            if (isset($done[$path]) || str_starts_with($path, self::BUNDLE_URL_PREFIX)) {
                continue;
            }

            $done[$path] = true;

            $this->assertSafePath($path, $source);
            $destination = rtrim($outputDir, '/').$path;
            $this->fetch($path, $destination, $source);
            ++$exported;

            if ('css' === strtolower(pathinfo($path, \PATHINFO_EXTENSION))) {
                foreach ($this->processStylesheet($path, $destination) as $referenced) {
                    $queue += [$referenced => $path];
                }
            }
        }

        return $exported;
    }

    /**
     * @return list<string>
     */
    private function processStylesheet(string $path, string $destination): array
    {
        $css = (string) file_get_contents($destination);

        $referenced = [];
        foreach ($this->urlRewriter->extractCssUrls($css) as $url) {
            $referenced[] = $this->pathOf(str_starts_with($url, '/') ? $url : $this->resolveRelative(\dirname($path), $url));
        }

        $this->filesystem->dumpFile($destination, $this->urlRewriter->rewriteCss($css, UrlRewriter::prefixFor(ltrim($path, '/'))));

        return $referenced;
    }

    private function fetch(string $path, string $destination, string $source): void
    {
        $file = $this->findPublicFile($path);
        if (null !== $file) {
            $this->filesystem->copy($file, $destination, true);

            return;
        }

        $response = $this->httpKernel->handle(Request::create(implode('/', array_map(rawurlencode(...), explode('/', $path)))));
        $this->assertValid($response, $path, $source);

        if ($response instanceof BinaryFileResponse) {
            $this->filesystem->copy($response->getFile()->getPathname(), $destination, true);

            return;
        }

        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();
            $content = (string) ob_get_clean();
        } else {
            $content = (string) $response->getContent();
        }

        $this->filesystem->dumpFile($destination, $content);
    }

    private function assertValid(Response $response, string $path, string $source): void
    {
        if (200 !== $response->getStatusCode()) {
            throw ExportException::forAsset($path, $source, \sprintf('not found in "%s" and the application answered HTTP %d.', $this->publicDir, $response->getStatusCode()));
        }

        // A catch-all route answering 200 with a page must not be mistaken for the asset.
        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));
        if ('' !== $extension && !\in_array($extension, ['html', 'htm'], true) && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            throw ExportException::forAsset($path, $source, \sprintf('not found in "%s" and the application answered with an HTML page.', $this->publicDir));
        }
    }

    /**
     * Symlinks are followed on purpose: "assets:install --symlink" and build tools commonly link
     * public/ entries to directories outside of it. Traversal is prevented upstream by assertSafePath().
     */
    private function findPublicFile(string $path): ?string
    {
        if (\in_array(strtolower(pathinfo($path, \PATHINFO_EXTENSION)), self::FORBIDDEN_EXTENSIONS, true)) {
            return null;
        }

        $file = rtrim($this->publicDir, '/').$path;

        return is_file($file) ? $file : null;
    }

    /**
     * @return list<string>
     */
    private function findGlobbedFiles(): array
    {
        if ([] === $this->globs || !is_dir($this->publicDir)) {
            return [];
        }

        $paths = [];
        foreach ((new Finder())->files()->in($this->publicDir)->path($this->toPatterns($this->globs))->ignoreDotFiles(true) as $file) {
            $paths[] = str_replace('\\', '/', $file->getRelativePathname());
        }

        sort($paths);

        return $paths;
    }

    /**
     * @param list<string> $globs
     *
     * @return list<string>
     */
    private function toPatterns(array $globs): array
    {
        return array_map(static fn (string $glob): string => '~^'.str_replace(['\\*\\*/', '\\*\\*', '\\*', '\\?'], ['(?:.*/)?', '.*', '[^/]*', '[^/]'], preg_quote(ltrim($glob, '/'), '~')).'$~', $globs);
    }

    private function pathOf(string $url): string
    {
        return rawurldecode((string) strtok((string) strtok($url, '#'), '?'));
    }

    private function resolveRelative(string $directory, string $url): string
    {
        $segments = [];
        foreach (explode('/', $directory.'/'.$url) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return '/'.implode('/', $segments);
    }

    private function assertSafePath(string $path, string $source): void
    {
        if ('' === $path || !str_starts_with($path, '/') || str_contains($path, "\0") || str_contains($path, '\\')) {
            throw ExportException::forAsset($path, $source, 'invalid path.');
        }

        foreach (explode('/', $path) as $segment) {
            if (str_starts_with($segment, '.')) {
                throw ExportException::forAsset($path, $source, 'hidden files and parent directories are never exported.');
            }
        }
    }
}
