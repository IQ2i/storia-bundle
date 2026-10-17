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

use IQ2i\StoriaBundle\Tests\TestApplication\Twig\Components\Level;

class NoticeFactory
{
    public static function danger(): Level
    {
        return Level::Danger;
    }

    public static function untrustedText(): string
    {
        return 'Hello {{ 7*7 }} {% if true %}yes{% endif %}';
    }
}
