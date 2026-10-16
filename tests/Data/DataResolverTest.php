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

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Data\DataRepository;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\Data\Exception\CircularDataReferenceException;
use IQ2i\StoriaBundle\Data\Exception\DataNotFoundException;
use IQ2i\StoriaBundle\Data\LoremIpsum;
use IQ2i\StoriaBundle\Tests\Fixtures\ProductFactory;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

class DataResolverTest extends TestCase
{
    private DataResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DataResolver(
            new DataRepository(__DIR__.'/../Fixtures/storia_data', new YamlPreProcessor()),
            new ArgResolver(new ServiceLocator([])),
        );
    }

    private function resolve(string $yaml): mixed
    {
        return $this->resolver->resolve(Yaml::parse($yaml, Yaml::PARSE_CUSTOM_TAGS));
    }

    public function testScalarsAndPlainArraysAreUntouched(): void
    {
        $this->assertSame(['a' => [1, 'b', null]], $this->resolver->resolve(['a' => [1, 'b', null]]));
    }

    public function testDataShortForm(): void
    {
        $resolved = $this->resolve('menu: !data menu');

        $this->assertSame('Shop', $resolved['menu']['items'][1]['label']);
    }

    public function testDataSubPath(): void
    {
        $this->assertSame(['menu' => '/'], $this->resolve('menu: !data menu.items.0.url'));
    }

    public function testDataLongFormWith(): void
    {
        $resolved = $this->resolve('p: !data { ref: shop/product.default, with: { price: 0 } }');

        $this->assertSame(['name' => 'Chair', 'price' => 0, 'inStock' => true], $resolved['p']);
    }

    public function testDataFileUsingTagsInsideItself(): void
    {
        $this->assertSame(['name' => 'Chair', 'price' => 999, 'inStock' => true], $this->resolve('p: !data shop/product.nested')['p']);
    }

    public function testDescendingThroughATaggedValue(): void
    {
        $this->assertSame(['p' => 999], $this->resolve('p: !data shop/product.expensive.price'));
    }

    public function testWithRequiresAMapping(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolve('p: !data { ref: menu.items.0.url, with: { a: 1 } }');
    }

    public function testDataRequiresRef(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolve('p: !data { with: { a: 1 } }');
    }

    public function testRepeatWithIndexAndNestedTags(): void
    {
        $resolved = $this->resolve("p: !repeat\n  count: 2\n  item:\n    name: 'Item {i}'\n    text: !lorem 2\n    price: !data shop/product.default.price");

        $this->assertSame([
            ['name' => 'Item 1', 'text' => 'lorem ipsum', 'price' => 49],
            ['name' => 'Item 2', 'text' => 'dolor sit', 'price' => 49],
        ], $resolved['p']);
    }

    public function testLoremVariesInsideRepeatedData(): void
    {
        $resolved = $this->resolve("p: !repeat\n  count: 3\n  item: !data { ref: shop/product.default, with: { name: !lorem 1 sentence } }");

        $names = array_column($resolved['p'], 'name');
        $this->assertCount(3, array_unique($names));
        $this->assertSame(LoremIpsum::sentences(1, 2), $names[2]);
    }

    public function testRepeatZero(): void
    {
        $this->assertSame(['p' => []], $this->resolve('p: !repeat { count: 0, item: x }'));
    }

    public function testRepeatInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->resolve('p: !repeat { count: -1, item: x }');
    }

    public function testLorem(): void
    {
        $this->assertSame(['t' => LoremIpsum::sentences(2)], $this->resolve('t: !lorem 2 sentences'));
    }

    public function testCircularReference(): void
    {
        $this->expectException(CircularDataReferenceException::class);
        $this->expectExceptionMessage('loop_b -> loop_a -> loop_b');
        $this->resolve('p: !data loop_a.value');
    }

    public function testUnknownData(): void
    {
        $this->expectException(DataNotFoundException::class);
        $this->resolve('p: !data nope');
    }

    public function testPhpReferenceTag(): void
    {
        $this->assertSame(ProductFactory::createStatic(), $this->resolve('p: !data '.ProductFactory::class.'::createStatic')['p']);
        $this->assertSame(ProductFactory::createStatic(), $this->resolve('p: !data php.product')['p']);
    }

    public function testBareClassMethodStillResolved(): void
    {
        $this->assertSame(ProductFactory::createStatic(), $this->resolver->resolve(['p' => ProductFactory::class.'::createStatic'])['p']);
    }

    public function testBareClassMethodIsDeprecated(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $this->resolver->resolve(['p' => ProductFactory::class.'::createStatic']);
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('use "!data '.ProductFactory::class.'::createStatic" instead', $deprecations[0]);
    }

    public function testUnknownTag(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown tag "!foo"');
        $this->resolver->resolve(new TaggedValue('foo', 'x'));
    }
}
