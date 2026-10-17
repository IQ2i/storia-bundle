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

namespace IQ2i\StoriaBundle\Export;

/**
 * @internal
 */
final readonly class ExportResult
{
    public function __construct(
        public int $views,
        public int $variants,
        public int $assets,
    ) {
    }
}
