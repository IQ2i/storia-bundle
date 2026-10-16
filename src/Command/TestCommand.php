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

use IQ2i\StoriaBundle\Test\Snapshot\SnapshotNormalizer;
use IQ2i\StoriaBundle\Test\Snapshot\SnapshotStore;
use IQ2i\StoriaBundle\Test\VariantRunner;
use IQ2i\StoriaBundle\View\Dto\View;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'storia:test', description: 'Render every variant and compare it with its HTML snapshot')]
final class TestCommand extends Command
{
    public function __construct(
        private readonly VariantRunner $runner,
        private readonly SnapshotStore $snapshots,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Only test the views or variants whose "view:variant" name contains this text')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Rewrite the snapshots that differ')
            ->addOption('no-snapshots', null, InputOption::VALUE_NONE, 'Only check that every variant renders')
            ->addOption('snapshot-path', null, InputOption::VALUE_REQUIRED, 'Directory of the snapshots (default: iq2i_storia.test.snapshot_path)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filter = $input->getOption('filter');
        $filter = \is_string($filter) && '' !== $filter ? $filter : null;

        $update = (bool) $input->getOption('update');
        $useSnapshots = !$input->getOption('no-snapshots');

        $snapshotPath = $input->getOption('snapshot-path');
        $store = \is_string($snapshotPath) && '' !== $snapshotPath ? $this->snapshots->withPath($snapshotPath) : $this->snapshots;

        $variantsByView = $this->runner->findVariants($filter);
        $counts = ['passed' => 0, 'new' => 0, 'updated' => 0, 'mismatched' => 0];

        $result = $this->runner->run($variantsByView, function (string $path, ?string $variant, ?View $view, ?\Throwable $error) use ($output, $store, $update, $useSnapshots, &$counts): void {
            $name = $path.(null === $variant ? '' : ':'.$variant);

            if (null !== $error) {
                $output->writeln(\sprintf('<error>✗ %s</error>', $name));
                $output->writeln(OutputFormatter::escape(\sprintf('    %s: %s', $error::class, $error->getMessage())));
                if ($output->isVerbose()) {
                    $output->writeln(OutputFormatter::escape($error->getTraceAsString()));
                }

                return;
            }

            if (!$useSnapshots || null === $view || null === $variant) {
                ++$counts['passed'];
                $output->writeln(\sprintf('<info>✓ %s</info>', $name));

                return;
            }

            $html = SnapshotNormalizer::normalize((string) $view->getHtmlContent());
            if (!$store->exists($path, $variant)) {
                $store->write($path, $variant, $html);
                ++$counts['new'];
                $output->writeln(\sprintf('<comment>+ %s (new snapshot)</comment>', $name));

                return;
            }

            $expected = $store->read($path, $variant);
            if ($expected === $html) {
                ++$counts['passed'];
                $output->writeln(\sprintf('<info>✓ %s</info>', $name));

                return;
            }

            if ($update) {
                $store->write($path, $variant, $html);
                ++$counts['updated'];
                $output->writeln(\sprintf('<comment>↻ %s (snapshot updated)</comment>', $name));

                return;
            }

            ++$counts['mismatched'];
            $output->writeln(\sprintf('<error>≠ %s</error>', $name));
            $output->writeln($this->diff($expected, $html));
        });

        if ($useSnapshots && null === $filter) {
            $known = [];
            foreach ($variantsByView as $path => $variants) {
                foreach (\is_array($variants) ? $variants : [] as $variant) {
                    $known[$path.':'.$variant] = true;
                }
            }

            foreach ($store->all() as $key) {
                if (!isset($known[$key])) {
                    $output->writeln(\sprintf('<comment>? %s (orphan snapshot, its variant no longer exists)</comment>', $key));
                }
            }
        }

        $failed = \count($result->failures) + $counts['mismatched'];
        $output->writeln('');
        $output->writeln(\sprintf(
            '%d variants, %d passed, %d new, %d updated, %d failed',
            $result->variants,
            $counts['passed'],
            $counts['new'],
            $counts['updated'],
            $failed,
        ));

        return 0 === $failed ? Command::SUCCESS : Command::FAILURE;
    }

    private function diff(string $expected, string $actual): string
    {
        if (!class_exists(Differ::class)) {
            return '    Run with --update to accept the new rendering.';
        }

        $diff = (new Differ(new UnifiedDiffOutputBuilder("--- snapshot\n+++ rendered\n")))->diff($expected, $actual);

        return rtrim(OutputFormatter::escape($diff));
    }
}
