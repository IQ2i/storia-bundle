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

use IQ2i\StoriaBundle\View\Dto\View;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

abstract class AbstractBuilder
{
    public function __construct(
        protected string $defaultPath,
        protected Environment $twig,
    ) {
    }

    abstract public function supports(string $path, array $config): bool;

    abstract public function build(Request $request, string $path, string $name, array $config): ?View;

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
}
