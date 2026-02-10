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

namespace IQ2i\StoriaBundle\Tests\Fixtures;

class ProductFactory
{
    public static function createStatic(): array
    {
        return [
            'name' => 'iPhone',
            'price' => 999,
        ];
    }

    public function create(): array
    {
        return [
            'name' => 'Product',
            'price' => 100,
        ];
    }

    protected function createProtected(): array
    {
        return ['protected' => true];
    }

    private function createPrivate(): array
    {
        return ['private' => true];
    }
}
