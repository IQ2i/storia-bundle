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

use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Michelf\MarkdownExtra;
use Symfony\Component\HttpFoundation\Request;

class TemplateBuilder extends AbstractBuilder
{
    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('template', $config) || @file_exists($this->defaultPath.'/'.$path.'.html.twig');
    }

    public function build(Request $request, string $path, array $config): ?View
    {
        $template = $config['template'] ?? null;

        $isPage = false;
        if (null === $template && @file_exists($this->defaultPath.'/'.$path.'.html.twig')) {
            $isPage = true;
            $template = $this->defaultPath.'/'.$path.'.html.twig';
        }

        $variantPath = $request->query->get('variant');
        if (isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $skeletonPath = __DIR__.'/../../../skeleton/template.tpl.php';

            $variantArgs = [];
            foreach ($variantConfig['args'] as $argName => $argValue) {
                $variantArgs[$argName] = $argValue;
            }

            $parameters = [
                'template' => $template,
                'args' => $variantArgs,
                'blocks' => $variantConfig['blocks'],
            ];

            $includeContent = null;
            if (!$isPage) {
                $includeContent = $this->generateInclude($skeletonPath, $parameters);
            }

            $twigContent = $this->getTwigContent($template);
            $htmlContent = $this->generateHtml($isPage ? $twigContent : $includeContent, $parameters['args']);

            $markdownContent = null;
            if (file_exists($this->defaultPath.'/'.$path.'.md')) {
                $markdownContent = MarkdownExtra::defaultTransform(@file_get_contents($this->defaultPath.'/'.$path.'.md'));
            }
        }

        $variants = array_map(static fn (string $name): Variant => new Variant(
            $name,
            ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name)))),
            $variantPath === $name
        ), array_keys($config['variants']));

        return new View(
            $path,
            $twigContent ?? null,
            $htmlContent ?? null,
            $includeContent ?? null,
            $markdownContent ?? null,
            $variants
        );
    }
}
