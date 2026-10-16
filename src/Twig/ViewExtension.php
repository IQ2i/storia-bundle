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

namespace IQ2i\StoriaBundle\Twig;

use IQ2i\StoriaBundle\Config\ViewConfiguration;
use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Data\DataResolver;
use Symfony\Component\Config\Definition\Processor;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViewExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $defaultPath,
        private readonly YamlPreProcessor $yamlPreProcessor,
        private readonly DataResolver $dataResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('iq2i_storia_variant_args', [$this, 'getVariantArgs']),
            new TwigFunction('iq2i_storia_variant_blocks', [$this, 'getVariantBlocks']),
        ];
    }

    public function getVariantArgs(string $view, string $variant): array
    {
        return $this->resolveVariant($view, $variant, 'args');
    }

    public function getVariantBlocks(string $view, string $variant): array
    {
        return $this->resolveVariant($view, $variant, 'blocks');
    }

    /**
     * @param 'args'|'blocks' $key
     *
     * @return array<string, mixed>
     */
    private function resolveVariant(string $view, string $variant, string $key): array
    {
        $config = (new Processor())->processConfiguration(
            new ViewConfiguration(),
            [$this->yamlPreProcessor->process($this->defaultPath, 'components/'.$view)]
        );

        if (!\array_key_exists($variant, $config['variants'])) {
            throw new \InvalidArgumentException('Unknown variant: '.$variant);
        }

        return $this->dataResolver->resolve($config['variants'][$variant][$key]);
    }
}
