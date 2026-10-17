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

namespace IQ2i\StoriaBundle\View\Builder;

use IQ2i\StoriaBundle\Util\Label;
use IQ2i\StoriaBundle\View\Dto\Variant;
use Michelf\MarkdownExtra;
use Twig\Environment;

/**
 * @internal
 */
abstract class AbstractBuilder implements BuilderInterface
{
    public function __construct(
        protected string $defaultPath,
        protected Environment $twig,
    ) {
    }

    protected function generateInclude(string $skeletonPath, array $parameters): string
    {
        ob_start();
        extract($parameters, \EXTR_SKIP);
        include $skeletonPath;

        return ob_get_clean();
    }

    protected function getTwigContent(string $template): string
    {
        if (str_starts_with($template, '/') && @file_exists($template)) {
            return @file_get_contents($template);
        }

        $source = $this->twig->getLoader()->getSourceContext($template);

        return $source->getCode();
    }

    protected function generateHtml(string $content, array $parameters): string
    {
        $template = $this->twig->createTemplate($content);

        return $template->render($parameters);
    }

    /**
     * @param array<string, mixed> $variantConfigs
     *
     * @return list<Variant>
     */
    protected function buildVariants(array $variantConfigs, ?string $variantPath): array
    {
        return array_map(static fn (string $name): Variant => new Variant(
            $name,
            $variantConfigs[$name]['name'] ?? Label::humanize($name),
            $variantPath === $name
        ), array_keys($variantConfigs));
    }

    protected function loadMarkdown(string $path): ?string
    {
        $file = $this->defaultPath.'/'.$path.'.md';
        if (!is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if (false === $content) {
            throw new \RuntimeException(\sprintf('The documentation file "%s" cannot be read.', $file));
        }

        return MarkdownExtra::defaultTransform($content);
    }
}
