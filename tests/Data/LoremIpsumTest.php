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

namespace IQ2i\StoriaBundle\Tests\Data;

use IQ2i\StoriaBundle\Data\LoremIpsum;
use PHPUnit\Framework\TestCase;

class LoremIpsumTest extends TestCase
{
    public function testWordsAreExactAndDeterministic(): void
    {
        $this->assertSame('lorem ipsum dolor', LoremIpsum::words(3));
        $this->assertSame(12, str_word_count(LoremIpsum::words(12)));
        $this->assertSame(LoremIpsum::words(100), LoremIpsum::words(100));
        $this->assertCount(100, explode(' ', LoremIpsum::words(100)));
    }

    public function testSentences(): void
    {
        $text = LoremIpsum::sentences(2);

        $this->assertSame(2, substr_count($text, '.'));
        $this->assertStringStartsWith('Lorem ipsum', $text);
    }

    public function testParagraphs(): void
    {
        $text = LoremIpsum::paragraphs(2);

        $this->assertCount(2, explode("\n\n", $text));
        $this->assertSame(8, substr_count($text, '.'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validSpecs')]
    public function testFromSpec(string $spec, string $expected): void
    {
        $this->assertSame($expected, LoremIpsum::fromSpec($spec));
    }

    public static function validSpecs(): iterable
    {
        yield ['3', LoremIpsum::words(3)];
        yield ['3 words', LoremIpsum::words(3)];
        yield ['1 word', LoremIpsum::words(1)];
        yield ['2 sentences', LoremIpsum::sentences(2)];
        yield ['1 sentence', LoremIpsum::sentences(1)];
        yield ['1 paragraph', LoremIpsum::paragraphs(1)];
        yield ['2 paragraphs', LoremIpsum::paragraphs(2)];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSpecs')]
    public function testInvalidSpec(string $spec): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LoremIpsum::fromSpec($spec);
    }

    public static function invalidSpecs(): iterable
    {
        yield [''];
        yield ['abc'];
        yield ['2 pages'];
        yield ['-1'];
    }
}
