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
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViewExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $defaultPath,
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
        $yaml = Yaml::parse(file_get_contents($this->defaultPath.'/components/'.$view.'.yaml'));
        $config = (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);

        if (!\array_key_exists($variant, $config['variants'])) {
            throw new \InvalidArgumentException('Unknown variant: '.$variant);
        }

        return $config['variants'][$variant]['args'];
    }

    public function getVariantBlocks(string $view, string $variant): array
    {
        $yaml = Yaml::parse(file_get_contents($this->defaultPath.'/components/'.$view.'.yaml'));
        $config = (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);

        if (!\array_key_exists($variant, $config['variants'])) {
            throw new \InvalidArgumentException('Unknown variant: '.$variant);
        }

        return $config['variants'][$variant]['blocks'];
    }
}
