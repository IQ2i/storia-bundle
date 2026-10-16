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

use IQ2i\StoriaBundle\Catalog\CatalogDescriber;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(name: 'storia:list', description: 'List the views of the catalog with their variants')]
final class ListCommand extends Command
{
    public function __construct(
        private readonly CatalogDescriber $describer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: txt or json', 'txt')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Only list the views or variants containing this text (view or view:variant)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = (string) $input->getOption('format');
        if (!\in_array($format, ['txt', 'json'], true)) {
            $output->writeln(\sprintf('<error>Unknown format "%s": use txt or json.</error>', $format));

            return Command::INVALID;
        }

        $filter = $input->getOption('filter');
        $views = $this->describer->describe(\is_string($filter) ? $filter : null);

        if ('json' === $format) {
            $output->writeln((string) json_encode($views, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR), OutputInterface::OUTPUT_RAW);

            return Command::SUCCESS;
        }

        foreach ($views as $view) {
            $output->writeln(\sprintf('<info>%s</info> (%s)', OutputFormatter::escape($view['path']), $view['type'] ?? 'error'));
            if (null !== $view['error']) {
                $output->writeln('  <error>'.OutputFormatter::escape($view['error']).'</error>');
            }

            foreach ($view['variants'] as $variant) {
                $output->writeln('  - '.OutputFormatter::escape($variant['name']).(null !== $variant['error'] ? ' <error>'.OutputFormatter::escape($variant['error']).'</error>' : ''));
            }
        }

        return Command::SUCCESS;
    }
}
