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

use Symfony\Component\Process\Process;

/**
 * Runs the console of the application. Mate has its own container and no kernel, whereas
 * rendering a variant needs the Twig environment of the application, so the tools ask the console.
 *
 * @internal
 */
final readonly class ConsoleRunner
{
    public function __construct(
        private string $console,
        private int $timeout = 120,
    ) {
    }

    /**
     * @param list<string> $arguments
     *
     * @return array{exitCode: int, output: string, error: string}
     */
    public function run(array $arguments): array
    {
        if (!is_file($this->console)) {
            throw new \RuntimeException(\sprintf('The console "%s" does not exist. Set the "ai_mate_storia.console" parameter in mate/config.php.', $this->console));
        }

        $process = new Process([\PHP_BINARY, $this->console, '--no-interaction', '--no-ansi', ...$arguments], \dirname($this->console, 2), null, null, $this->timeout);
        $process->run();

        return ['exitCode' => (int) $process->getExitCode(), 'output' => $process->getOutput(), 'error' => trim($process->getErrorOutput())];
    }
}
