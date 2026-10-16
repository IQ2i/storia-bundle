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
use IQ2i\StoriaBundle\Data\Exception\DataNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Tag\TaggedValue;

class DataRepositoryTest extends TestCase
{
    private DataRepository $repository;

    private \Closure $resolveTag;

    protected function setUp(): void
    {
        $this->repository = new DataRepository(__DIR__.'/../Fixtures/storia_data', new YamlPreProcessor());
        $this->resolveTag = static fn (TaggedValue $tag): mixed => $tag->getValue();
    }

    public function testFlatFile(): void
    {
        $data = $this->repository->get('menu', $this->resolveTag);

        $this->assertSame('Home', $data['items'][0]['label']);
    }

    public function testSubDirectory(): void
    {
        $this->assertSame(49, $this->repository->get('shop/product.default.price', $this->resolveTag));
    }

    public function testDottedPathAndListIndex(): void
    {
        $this->assertSame('/shop', $this->repository->get('menu.items.1.url', $this->resolveTag));
    }

    public function testMissingFile(): void
    {
        $this->expectException(DataNotFoundException::class);
        $this->expectExceptionMessage('Data "nope" not found: expected file');
        $this->expectExceptionMessage('/data/nope.yaml');
        $this->repository->get('nope', $this->resolveTag);
    }

    public function testMissingKey(): void
    {
        $this->expectException(DataNotFoundException::class);
        $this->expectExceptionMessage('Key "items.9" not found in data "menu".');
        $this->repository->get('menu.items.9', $this->resolveTag);
    }

    public function testParentDirectoryIsRefused(): void
    {
        $this->expectException(DataNotFoundException::class);
        $this->repository->get('../../Config/YamlPreProcessorTest', $this->resolveTag);
    }

    public function testTagOnTheWayIsResolved(): void
    {
        $resolved = $this->repository->get('shop/product.expensive.price', static fn (): array => ['price' => 1]);

        $this->assertSame(1, $resolved);
    }
}
