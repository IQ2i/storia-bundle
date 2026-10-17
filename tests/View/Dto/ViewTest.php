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

namespace IQ2i\StoriaBundle\Tests\View\Dto;

use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function testCurrentVariant(): void
    {
        $current = new Variant('large', 'Large', true);
        $view = new View('components/badge', variants: [new Variant('default', 'Default'), $current]);

        $this->assertSame($current, $view->getCurrentVariant());
    }

    public function testNoCurrentVariant(): void
    {
        $this->assertNull((new View('components/badge', variants: [new Variant('default', 'Default')]))->getCurrentVariant());
        $this->assertNull((new View('components/badge'))->getCurrentVariant());
    }
}
