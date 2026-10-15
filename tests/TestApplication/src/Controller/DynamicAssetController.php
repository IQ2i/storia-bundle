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

namespace IQ2i\StoriaBundle\Tests\TestApplication\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * Simulates an asset that only exists through the application (AssetMapper in dev).
 */
#[AsController]
final class DynamicAssetController
{
    public function __invoke(): Response
    {
        return new Response('.storia-dynamic { background: url(/img/pixel.svg); }', 200, ['Content-Type' => 'text/css']);
    }
}
