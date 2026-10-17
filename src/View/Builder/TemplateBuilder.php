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
use IQ2i\StoriaBundle\View\Dto\View;
use Twig\Environment;

/**
 * @internal
 */
class TemplateBuilder extends AbstractBuilder
{
    public function __construct(
        string $defaultPath,
        Environment $twig,
        string $projectDir,
        private readonly DataResolver $dataResolver,
        private readonly ControlsBuilder $controlsBuilder,
    ) {
        parent::__construct($defaultPath, $twig, $projectDir);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('template', $config) || is_file($this->defaultPath.'/'.$path.'.html.twig');
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $overrides
     */
    public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View
    {
        $template = $config['template'] ?? null;

        if (null === $template && is_file($this->defaultPath.'/'.$path.'.html.twig')) {
            $template = $this->defaultPath.'/'.$path.'.html.twig';
        }

        $controls = [];
        $appliedOverrides = [];
        $twigContent = null;
        $htmlContent = null;
        $markdownContent = null;

        if (null !== $variantPath && isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $resolvedArgs = $this->dataResolver->resolve($variantConfig['args']);
            $twigContent = $this->getTwigContent($template);

            ['controls' => $controls, 'overrides' => $renderOverrides, 'display' => $appliedOverrides] = $this->controlsBuilder->applyOverrides(
                $this->controlsBuilder->forTemplate($resolvedArgs, $twigContent),
                $overrides
            );

            $htmlContent = $this->generateHtml($twigContent, array_replace($resolvedArgs, $renderOverrides));

            $markdownContent = $this->loadMarkdown($path);
        }

        $variants = $this->buildVariants($config['variants'], $variantPath);

        return new View(
            $path,
            $twigContent,
            $htmlContent,
            null,
            $markdownContent,
            $variants,
            $controls,
            $appliedOverrides
        );
    }
}
