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

use IQ2i\StoriaBundle\Export\ExportException;
use IQ2i\StoriaBundle\Export\StaticExporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(name: 'storia:export', description: 'Export the catalog as a static website')]
final class ExportCommand extends Command
{
    public function __construct(
        private readonly StaticExporter $exporter,
        private readonly string $defaultOutputDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('output-dir', InputArgument::OPTIONAL, 'Directory receiving the export', $this->defaultOutputDir);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputDir = (string) $input->getArgument('output-dir');

        try {
            $result = $this->exporter->export($outputDir, static function (string $view, string $variant) use ($output): void {
                $output->writeln(\sprintf('<info>Exported</info> %s:%s', $view, $variant));
            });
        } catch (ExportException $exportException) {
            $output->writeln(\sprintf('<error>%s</error>', $exportException->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(\sprintf(
            '<info>Exported %d views, %d variants and %d assets to %s</info>',
            $result->views,
            $result->variants,
            $result->assets,
            $outputDir
        ));

        return Command::SUCCESS;
    }
}
