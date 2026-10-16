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

namespace IQ2i\StoriaBundle\Tests\Controls;

use IQ2i\StoriaBundle\Controls\OverridesParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class OverridesParserTest extends TestCase
{
    private function parse(mixed $overrides): array
    {
        return OverridesParser::fromRequest(Request::create('/', 'GET', null === $overrides ? [] : ['overrides' => $overrides]));
    }

    public function testValidObject(): void
    {
        $this->assertSame(['title' => 'Hi', 'n' => 1, 'list' => ['a']], $this->parse('{"title":"Hi","n":1,"list":["a"]}'));
    }

    public function testMissingParameter(): void
    {
        $this->assertSame([], $this->parse(null));
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidValuesAreIgnored(mixed $overrides): void
    {
        $this->assertSame([], $this->parse($overrides));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'malformed' => ['{"title":'];
        yield 'empty' => [''];
        yield 'list' => ['["a","b"]'];
        yield 'scalar' => ['"text"'];
        yield 'null' => ['null'];
        yield 'empty object' => ['{}'];
        yield 'array parameter' => [['title' => 'x']];
        yield 'too big' => ['{"a":"'.str_repeat('a', 70000).'"}'];
        yield 'too deep' => [str_repeat('{"a":', 20).'1'.str_repeat('}', 20)];
    }
}
