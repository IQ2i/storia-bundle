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

use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\ViewBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
#[AsCommand(name: 'storia:render', description: 'Print what a variant renders')]
final class RenderCommand extends Command
{
    private const array FORMATS = ['html', 'twig', 'include'];

    public function __construct(
        private readonly ViewBuilder $viewBuilder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('view', InputArgument::REQUIRED, 'Path of the view, such as components/badge')
            ->addArgument('variant', InputArgument::REQUIRED, 'Name of the variant')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'What to print: html (the rendered HTML), twig (the template source) or include (code to use the component)', 'html');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errors = (new SymfonyStyle($input, $output))->getErrorStyle();

        $format = (string) $input->getOption('format');
        if (!\in_array($format, self::FORMATS, true)) {
            $errors->error(\sprintf('Unknown format "%s": use %s.', $format, implode(', ', self::FORMATS)));

            return Command::INVALID;
        }

        $path = (string) $input->getArgument('view');
        $variant = (string) $input->getArgument('variant');

        try {
            $view = $this->viewBuilder->create($path, $variant);
        } catch (\Throwable $throwable) {
            $errors->error(\sprintf('Variant "%s:%s" failed: %s', $path, $variant, $throwable->getMessage()));

            return Command::FAILURE;
        }

        if (null === $view) {
            $errors->error(\sprintf('View "%s" not found. Run storia:list to see the available views.', $path));

            return Command::FAILURE;
        }

        if (!\in_array($variant, array_map(static fn (Variant $v): string => $v->getPath(), $view->getVariants()), true)) {
            $errors->error(\sprintf('View "%s" has no variant "%s". Run storia:list to see the available variants.', $path, $variant));

            return Command::FAILURE;
        }

        $content = match ($format) {
            'html' => $view->getHtmlContent(),
            'twig' => $view->getTwigContent(),
            default => $view->getIncludeContent(),
        };

        if (null === $content) {
            $errors->error(\sprintf('View "%s" has no "%s" content.', $path, $format));

            return Command::FAILURE;
        }

        $output->write($content, true, OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }
}
