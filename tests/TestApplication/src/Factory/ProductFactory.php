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

namespace IQ2i\StoriaBundle\Tests\TestApplication\Factory;

class ProductFactory
{
    public static function createDefault(): array
    {
        return [
            'id' => 1,
            'name' => 'Test Product',
            'price' => 29.99,
            'inStock' => true,
        ];
    }

    public static function createExpensive(): array
    {
        return [
            'id' => 2,
            'name' => 'Premium Product',
            'price' => 999.99,
            'inStock' => true,
        ];
    }

    public static function createOutOfStock(): array
    {
        return [
            'id' => 3,
            'name' => 'Unavailable Product',
            'price' => 49.99,
            'inStock' => false,
        ];
    }
}
