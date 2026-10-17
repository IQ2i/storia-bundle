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

namespace IQ2i\StoriaBundle\Tests\Util;

use IQ2i\StoriaBundle\Util\Label;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LabelTest extends TestCase
{
    #[DataProvider('provideNames')]
    public function testHumanize(string $name, string $expected): void
    {
        $this->assertSame($expected, Label::humanize($name));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideNames(): iterable
    {
        yield 'word' => ['button', 'Button'];
        yield 'camel case' => ['buttonGroup', 'Button group'];
        yield 'pascal case' => ['ButtonGroup', 'Button group'];
        yield 'snake case' => ['extra_large', 'Extra large'];
        yield 'spaces' => ['  extra   large ', 'Extra large'];
        yield 'empty' => ['', ''];
    }
}
