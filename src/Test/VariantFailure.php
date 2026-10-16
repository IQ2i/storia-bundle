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

namespace IQ2i\StoriaBundle\Test;

final readonly class VariantFailure
{
    public function __construct(
        public string $path,
        public ?string $variant,
        public \Throwable $exception,
    ) {
    }

    public function name(): string
    {
        return $this->path.(null === $this->variant ? '' : ':'.$this->variant);
    }
}
