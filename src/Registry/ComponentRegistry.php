<?php

/*
 * This file is part of the Arqui project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\ArquiBundle\Registry;

use IQ2i\ArquiBundle\Dto\Component;
use IQ2i\ArquiBundle\Dto\Variant;
use Twig\Environment;

readonly class ComponentRegistry
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * @return array<Variant>
     */
    public function findComponentVariants(Component $component): array
    {
        $blocks = $this->twig->load($component->getPath())->getBlockNames();

        return array_map(
            static fn (string $block): Variant => new Variant(
                $block,
                ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $block))))
            ), $blocks
        );
    }

    public function findFirstComponentVariant(Component $component): Variant
    {
        $variants = $this->findComponentVariants($component);

        return array_shift($variants);
    }
}
