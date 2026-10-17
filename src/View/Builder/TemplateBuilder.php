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

use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\Util\Label;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Michelf\MarkdownExtra;
use Twig\Environment;

/**
 * @internal
 */
class TemplateBuilder extends AbstractBuilder
{
    public function __construct(
        string $defaultPath,
        Environment $twig,
        private readonly DataResolver $dataResolver,
        private readonly ControlsBuilder $controlsBuilder,
    ) {
        parent::__construct($defaultPath, $twig);
    }

    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('template', $config) || @file_exists($this->defaultPath.'/'.$path.'.html.twig');
    }

    public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View
    {
        $template = $config['template'] ?? null;

        if (null === $template && @file_exists($this->defaultPath.'/'.$path.'.html.twig')) {
            $template = $this->defaultPath.'/'.$path.'.html.twig';
        }

        $controls = [];
        $appliedOverrides = [];

        if (null !== $variantPath && isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $resolvedArgs = $this->dataResolver->resolve($variantConfig['args']);
            $twigContent = $this->getTwigContent($template);

            ['controls' => $controls, 'overrides' => $renderOverrides, 'display' => $appliedOverrides] = $this->controlsBuilder->applyOverrides(
                $this->controlsBuilder->forTemplate($resolvedArgs, $twigContent),
                $overrides
            );

            $htmlContent = $this->generateHtml($twigContent, array_replace($resolvedArgs, $renderOverrides));

            $markdownContent = null;
            if (file_exists($this->defaultPath.'/'.$path.'.md')) {
                $markdownContent = MarkdownExtra::defaultTransform(@file_get_contents($this->defaultPath.'/'.$path.'.md'));
            }
        }

        $variants = array_map(static fn (string $name): Variant => new Variant(
            $name,
            $config['variants'][$name]['name'] ?? Label::humanize($name),
            $variantPath === $name
        ), array_keys($config['variants']));

        return new View(
            $path,
            $twigContent ?? null,
            $htmlContent ?? null,
            null,
            $markdownContent ?? null,
            $variants,
            $controls,
            $appliedOverrides
        );
    }
}
