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

use IQ2i\StoriaBundle\Util\TwigLiteral;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

enum TwigLiteralSuit: string
{
    case Hearts = 'hearts';
}

class TwigLiteralTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideValues(): iterable
    {
        yield 'apostrophe' => ["It's"];
        yield 'backslash' => ['C:\\temp\\new'];
        yield 'double quote' => ['say "hi"'];
        yield 'accent' => ['Café à Noël é'];
        yield 'numeric string' => ['007'];
        yield 'phone number' => ['+33612345678'];
        yield 'url' => ['https://example.com/a/b?c=d&e=f'];
        yield 'new lines' => ["a\nb\r\tc"];
        yield 'twig syntax' => ['{{ 7*7 }} {% if true %}x{% endif %} #{1+1}'];
        yield 'empty string' => [''];
        yield 'int' => [42];
        yield 'negative int' => [-3];
        yield 'float' => [1.5];
        yield 'round float' => [2.0];
        yield 'big float' => [1.0e25];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'list' => [['a', 1, true, null]];
        yield 'map' => [['name' => "O'Neil", 'age' => 7, "quoted key's" => 'x']];
        yield 'nested' => [['items' => [['id' => 1, 'tags' => ['a', 'b']], ['id' => 2, 'tags' => []]], 'meta' => []]];
        yield 'int keys' => [[3 => 'a', 5 => 'b']];
    }

    #[DataProvider('provideValues')]
    public function testLiteralIsEvaluatedByTwigToTheSameValue(mixed $value): void
    {
        $twig = new Environment(new ArrayLoader([
            'page' => '{% set value = '.TwigLiteral::export($value).' %}{{ value|json_encode|raw }}',
        ]));

        $this->assertSame(json_encode($value), $twig->render('page'));
    }

    public function testLiteralCanSitInAnHtmlAttribute(): void
    {
        $this->assertStringNotContainsString('"', TwigLiteral::export(['say "hi"']));
    }

    public function testEnum(): void
    {
        $literal = TwigLiteral::export(TwigLiteralSuit::Hearts);
        $twig = new Environment(new ArrayLoader(['page' => '{{ '.$literal.'.value }}']));

        $this->assertSame("constant('IQ2i\\\\StoriaBundle\\\\Tests\\\\Util\\\\TwigLiteralSuit::Hearts')", $literal);
        $this->assertSame('hearts', $twig->render('page'));
    }

    public function testDate(): void
    {
        $twig = new Environment(new ArrayLoader(['page' => '{{ '.TwigLiteral::export(new \DateTimeImmutable('2026-03-01T10:30:00+00:00')).'|date("Y-m-d H:i") }}']));

        $this->assertSame('2026-03-01 10:30', $twig->render('page'));
    }

    public function testObjectIsDescribedByItsClass(): void
    {
        $this->assertSame("'(stdClass object)'", TwigLiteral::export(new \stdClass()));
    }

    public function testNonFiniteFloatBecomesNull(): void
    {
        $this->assertSame('null', TwigLiteral::export(\INF));
        $this->assertSame('null', TwigLiteral::export(\NAN));
    }
}
