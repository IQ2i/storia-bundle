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

namespace IQ2i\StoriaBundle\Url;

interface LinkGeneratorInterface
{
    public function view(string $path, ?string $variant = null): string;

    public function iframe(string $path, ?string $variant = null): string;

    public function asset(string $path): string;
}
