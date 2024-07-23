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
use Symfony\Component\HttpFoundation\Request;

interface BuilderInterface
{
    public function supports(string $path, array $config): bool;

    public function build(Request $request, string $path, array $config): ?View;
}
