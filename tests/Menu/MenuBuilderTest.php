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

namespace IQ2i\StoriaBundle\Tests\Menu;

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Menu\MenuBuilder;
use IQ2i\StoriaBundle\Url\UrlGeneratorInterface;
use IQ2i\StoriaBundle\View\Builder\BuilderInterface;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use IQ2i\StoriaBundle\View\ViewBuilder;
use PHPUnit\Framework\TestCase;

class MenuBuilderTest extends TestCase
{
    public function testCreateSidebarMenu(): void
    {
        $menu = $this->createBuilder()->createSidebarMenu($this->createUrls(), 'components/avatar');

        $this->assertCount(2, $menu->getChildren());

        $components = $menu->getChildren()[0];
        $this->assertCount(8, $components->getChildren());

        $avatar = $components->getChildren()[0];
        $this->assertEquals('Avatar', $avatar->getLabel());
        $this->assertEquals('/components/avatar?first', $avatar->getUrl());
        $this->assertTrue($avatar->isActive());

        $badge = $components->getChildren()[1];
        $this->assertEquals('Badge', $badge->getLabel());
        $this->assertEquals('/components/badge?first', $badge->getUrl());
        $this->assertFalse($badge->isActive());

        $pages = $menu->getChildren()[1];
        $this->assertCount(1, $pages->getChildren());
    }

    public function testActiveItemOpensItsParents(): void
    {
        $menu = $this->createBuilder()->createSidebarMenu($this->createUrls(), 'components/form/text');

        $this->assertFalse($menu->getChildren()[0]->getChildren()[0]->isActive());
        $form = array_values(array_filter(
            $menu->getChildren()[0]->getChildren(),
            static fn ($child): bool => 'Form' === $child->getLabel()
        ))[0];
        $this->assertTrue($form->isOpened());
        $this->assertTrue($menu->getChildren()[0]->isOpened());
    }

    public function testFirstVariantsCanBeProvided(): void
    {
        $menu = $this->createBuilder(static fn () => throw new \LogicException('The views must not be built.'))->createSidebarMenu(
            $this->createUrls(),
            null,
            ['components/avatar' => 'large']
        );

        $this->assertEquals('/components/avatar?large', $menu->getChildren()[0]->getChildren()[0]->getUrl());
        $this->assertEquals('/components/badge?', $menu->getChildren()[0]->getChildren()[1]->getUrl());
    }

    public function testBrokenViewDoesNotBreakTheMenu(): void
    {
        $menu = $this->createBuilder(static fn () => throw new \RuntimeException('broken'))->createSidebarMenu($this->createUrls(), null);

        $this->assertEquals('/components/avatar?', $menu->getChildren()[0]->getChildren()[0]->getUrl());
    }

    /**
     * @param ?\Closure(string): View $build
     */
    private function createBuilder(?\Closure $build = null): MenuBuilder
    {
        $build ??= static fn (string $path): View => new View($path, variants: [
            new Variant('first', 'First'),
            new Variant('second', 'Second'),
        ]);

        $builder = new class($build) implements BuilderInterface {
            public function __construct(private readonly \Closure $build)
            {
            }

            public function supports(string $path, array $config): bool
            {
                return true;
            }

            public function build(string $path, ?string $variantPath, array $config): ?View
            {
                return ($this->build)($path);
            }
        };

        $defaultPath = \dirname(__DIR__).'/TestApplication/storia';

        return new MenuBuilder($defaultPath, new ViewBuilder($defaultPath, [$builder], new YamlPreProcessor()));
    }

    private function createUrls(): UrlGeneratorInterface
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('view')->willReturnCallback(static fn (string $path, ?string $variant = null): string => '/'.$path.'?'.$variant);

        return $urls;
    }
}
