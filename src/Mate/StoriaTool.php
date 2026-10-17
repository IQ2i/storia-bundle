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

namespace IQ2i\StoriaBundle\Mate;

use Symfony\AI\Mate\Attribute\MateTool;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

/**
 * Tools of the UI Storia catalog for Symfony Mate.
 *
 * @internal
 */
final readonly class StoriaTool
{
    private const array RENDER_FORMATS = ['html', 'twig', 'include'];

    public function __construct(
        private ConsoleRunner $console,
    ) {
    }

    /**
     * @param string|null $filter Only views or variants containing this text, such as components/button or components/button:large
     */
    #[MateTool(name: 'storia-list', title: 'UI Storia List', description: 'List the views of the UI Storia catalog (templates, Twig Components, form types and pages) with their type, source, YAML file, variants and the resolved arguments of each variant. A view that cannot be loaded carries its error. Use it before writing or changing a story, to follow the conventions of the project.')]
    public function list(?string $filter = null): string
    {
        $arguments = ['storia:list', '--format=json'];
        if (null !== $filter && '' !== $filter) {
            $arguments[] = '--filter='.$filter;
        }

        $result = $this->console->run($arguments);
        if (0 !== $result['exitCode']) {
            throw new \RuntimeException($this->failure('storia:list', $result));
        }

        return ResponseEncoder::encodeUntrusted(['views' => json_decode($result['output'], true, 512, \JSON_THROW_ON_ERROR)]);
    }

    /**
     * @param string $view    Path of the view, such as components/badge
     * @param string $variant Key of the variant, as written in the YAML file
     * @param string $format  html (the rendered HTML), twig (the template source) or include (code to use the component, components only)
     */
    #[MateTool(name: 'storia-render', title: 'UI Storia Render', description: 'Render one variant of a UI Storia view and return its HTML, the source of its template, or the code to include the component. The quickest way to check that a variant renders: it fails with the Twig or PHP error otherwise. It does not show the CSS.')]
    public function render(string $view, string $variant, string $format = 'html'): string
    {
        if (!\in_array($format, self::RENDER_FORMATS, true)) {
            throw new \InvalidArgumentException(\sprintf('Unknown format "%s": use %s.', $format, implode(', ', self::RENDER_FORMATS)));
        }

        $result = $this->console->run(['storia:render', '--format='.$format, '--', $view, $variant]);
        if (0 !== $result['exitCode']) {
            throw new \RuntimeException($this->failure('storia:render', $result));
        }

        return ResponseEncoder::encodeUntrusted(['view' => $view, 'variant' => $variant, 'format' => $format, 'content' => rtrim($result['output'], "\n")]);
    }

    /**
     * @param string|null $filter    Only views or variants containing this text, such as components/button or components/button:large
     * @param bool        $snapshots Compare the HTML with the snapshots. A variant that has no snapshot yet gets one written, and nothing is ever updated
     */
    #[MateTool(name: 'storia-test', title: 'UI Storia Test', description: 'Render every variant of the UI Storia catalog and report the ones that cannot be rendered or whose HTML differs from its snapshot. Returns the success flag, the summary line and the output of the command. Never accepts a changed snapshot: a difference is reported, not fixed.')]
    public function test(?string $filter = null, bool $snapshots = true): string
    {
        $arguments = ['storia:test'];
        if (null !== $filter && '' !== $filter) {
            $arguments[] = '--filter='.$filter;
        }

        if (!$snapshots) {
            $arguments[] = '--no-snapshots';
        }

        $result = $this->console->run($arguments);
        if ('' === trim($result['output']) && '' !== $result['error']) {
            throw new \RuntimeException($this->failure('storia:test', $result));
        }

        return ResponseEncoder::encodeUntrusted(['passed' => 0 === $result['exitCode'], 'output' => $result['output']]);
    }

    /**
     * @param array{exitCode: int, output: string, error: string} $result
     */
    private function failure(string $command, array $result): string
    {
        $message = '' !== $result['error'] ? $result['error'] : $result['output'];
        // The console draws its errors in a box that wraps the text
        $message = trim((string) preg_replace('/\s+/', ' ', str_replace('[ERROR]', '', $message)));

        return \sprintf('%s failed (exit code %d): %s', $command, $result['exitCode'], $message);
    }
}
