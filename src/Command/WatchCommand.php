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

namespace IQ2i\StoriaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'storia:watch', description: 'Watch files and trigger live reload')]
final class WatchCommand extends Command
{
    private readonly string $tmpFile;

    public function __construct(
        private readonly string $defaultPath,
        private readonly array $watchPaths,
        private readonly string $projectDir,
    ) {
        parent::__construct();
        $this->tmpFile = $this->projectDir.'/var/storia_reload.json';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $binaryPath = $this->getBinaryPath();

        if (!file_exists($binaryPath)) {
            $output->writeln(\sprintf('<error>Watcher binary not found: %s</error>', $binaryPath));

            return Command::FAILURE;
        }

        chmod($binaryPath, 0o755);

        // Map path => reload type
        $watchMap = [$this->defaultPath => 'page'];
        foreach ($this->watchPaths as $path) {
            $watchMap[$path] = 'iframe';
        }

        /** @var array<string, array{process: Process, type: string}> $processes */
        $processes = [];
        foreach ($watchMap as $path => $type) {
            $realPath = realpath($path);
            if (false === $realPath) {
                $output->writeln(\sprintf('<comment>Skipping non-existent directory: %s</comment>', $path));
                continue;
            }

            $process = new Process([$binaryPath, $realPath.'/...']);
            $process->setTimeout(null);
            $process->start();
            $processes[$realPath] = ['process' => $process, 'type' => $type];
            $output->writeln(\sprintf('<info>Watching [%s]:</info> %s', $type, $realPath));
        }

        if ([] === $processes) {
            $output->writeln('<error>No directories to watch.</error>');

            return Command::FAILURE;
        }

        $running = true;

        if (\function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            $stop = function () use (&$running, $processes): void {
                $running = false;
                foreach ($processes as ['process' => $process]) {
                    $process->stop(5);
                }

                file_put_contents($this->tmpFile, json_encode([
                    'type' => 'stop',
                    'at' => microtime(true),
                ]), \LOCK_EX);
            };
            pcntl_signal(\SIGINT, $stop);
            pcntl_signal(\SIGTERM, $stop);
        }

        $varDir = \dirname($this->tmpFile);
        if (!is_dir($varDir)) {
            mkdir($varDir, 0o777, true);
        }

        $output->writeln('<info>Watching for changes… (Ctrl+C to stop)</info>');

        while ($running) {
            foreach ($processes as ['process' => $process, 'type' => $type]) {
                $stdout = $process->getIncrementalOutput();
                if ('' === $stdout) {
                    continue;
                }

                foreach (explode("\n", $stdout) as $line) {
                    $line = trim($line);
                    if ('' === $line) {
                        continue;
                    }

                    $event = json_decode($line, true);
                    if (null === $event || !isset($event['name'])) {
                        continue;
                    }

                    file_put_contents($this->tmpFile, json_encode([
                        'type' => $type,
                        'at' => microtime(true),
                    ]), \LOCK_EX);
                    $reload = 'page' === $type ? '<info>→ page reload</info>' : '<comment>→ iframe reload</comment>';
                    $output->writeln(\sprintf(
                        '[%s] Change detected: <options=bold>%s</> (%s) %s',
                        date('H:i:s'),
                        $event['name'],
                        $event['operation'] ?? '?',
                        $reload,
                    ));
                }
            }

            usleep(100_000);
        }

        return Command::SUCCESS;
    }

    private function getBinaryPath(): string
    {
        $os = strtolower(php_uname('s'));
        $arch = php_uname('m');

        $name = match (true) {
            str_contains($os, 'darwin') && 'arm64' === $arch => 'watcher-darwin-arm64',
            str_contains($os, 'darwin') => 'watcher-darwin-amd64',
            str_contains($os, 'linux') && str_contains($arch, 'aarch64') => 'watcher-linux-arm64',
            str_contains($os, 'linux') => 'watcher-linux-amd64',
            str_contains($os, 'windows') => 'watcher-windows.exe',
            default => throw new \RuntimeException(\sprintf('Unsupported OS: %s', $os)),
        };

        return \dirname(__DIR__, 2).'/tools/watcher/bin/'.$name;
    }
}
