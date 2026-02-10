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

namespace IQ2i\StoriaBundle\Tests\View\Resolver;

use IQ2i\StoriaBundle\Tests\Fixtures\ProductFactory;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ArgResolverTest extends TestCase
{
    public function testStaticValuesPassThroughUnchanged(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'string' => 'test',
            'number' => 42,
            'bool' => true,
            'null' => null,
            'array' => ['a', 'b', 'c'],
        ];

        $resolved = $resolver->resolve($args);

        $this->assertSame($args, $resolved);
    }

    public function testStaticMethodIsCalledCorrectly(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'product' => ProductFactory::class.'::createStatic',
        ];

        $resolved = $resolver->resolve($args);

        $this->assertEquals([
            'product' => [
                'name' => 'iPhone',
                'price' => 999,
            ],
        ], $resolved);
    }

    public function testInstanceMethodIsCalledViaContainer(): void
    {
        $factory = new ProductFactory();

        $serviceLocator = $this->createMock(ContainerInterface::class);
        $serviceLocator->expects($this->once())
            ->method('has')
            ->with(ProductFactory::class)
            ->willReturn(true);
        $serviceLocator->expects($this->once())
            ->method('get')
            ->with(ProductFactory::class)
            ->willReturn($factory);

        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'product' => ProductFactory::class.'::create',
        ];

        $resolved = $resolver->resolve($args);

        $this->assertEquals([
            'product' => [
                'name' => 'Product',
                'price' => 100,
            ],
        ], $resolved);
    }

    public function testRecursiveResolutionInNestedArrays(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'items' => [
                'static' => 'value',
                'dynamic' => ProductFactory::class.'::createStatic',
                'nested' => [
                    'deep' => ProductFactory::class.'::createStatic',
                ],
            ],
        ];

        $resolved = $resolver->resolve($args);

        $this->assertEquals([
            'items' => [
                'static' => 'value',
                'dynamic' => [
                    'name' => 'iPhone',
                    'price' => 999,
                ],
                'nested' => [
                    'deep' => [
                        'name' => 'iPhone',
                        'price' => 999,
                    ],
                ],
            ],
        ], $resolved);
    }

    public function testThrowsExceptionForNonExistentClass(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'product' => 'App\\NonExistent\\Class::method',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve argument "App\\NonExistent\\Class::method": class "App\\NonExistent\\Class" does not exist.');

        $resolver->resolve($args);
    }

    public function testThrowsExceptionForNonExistentMethod(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'product' => ProductFactory::class.'::nonExistentMethod',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve argument "'.ProductFactory::class.'::nonExistentMethod": method "nonExistentMethod" does not exist in class "'.ProductFactory::class.'".');

        $resolver->resolve($args);
    }

    public function testThrowsExceptionForInstanceMethodNotInContainer(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $serviceLocator->expects($this->once())
            ->method('has')
            ->with(ProductFactory::class)
            ->willReturn(false);

        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'product' => ProductFactory::class.'::create',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve argument "'.ProductFactory::class.'::create": method "'.ProductFactory::class.'::create" is not static and class is not available in the service container.');

        $resolver->resolve($args);
    }

    public function testThrowsExceptionForNonPublicMethod(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'product' => ProductFactory::class.'::createProtected',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve argument "'.ProductFactory::class.'::createProtected": method "'.ProductFactory::class.'::createProtected" is not public.');

        $resolver->resolve($args);
    }

    public function testStringsWithColonButNotMatchingPatternPassThrough(): void
    {
        $serviceLocator = $this->createMock(ContainerInterface::class);
        $resolver = new ArgResolver($serviceLocator);

        $args = [
            'url' => 'https://example.com',
            'time' => '12:30:45',
            'invalid' => 'Not::A::Valid::Pattern',
            'spaces' => 'Class Name::method',
        ];

        $resolved = $resolver->resolve($args);

        $this->assertSame($args, $resolved);
    }
}
