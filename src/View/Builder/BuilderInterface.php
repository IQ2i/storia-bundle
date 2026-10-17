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

/**
 * @internal
 */
interface BuilderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function supports(string $path, array $config): bool;

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $overrides literal values replacing the resolved args of the variant
     */
    public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View;
}
