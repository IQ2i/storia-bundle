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
use IQ2i\StoriaBundle\Url\LinkGeneratorInterface;
use IQ2i\StoriaBundle\View\Builder\BuilderInterface;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use IQ2i\StoriaBundle\View\ViewBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class MenuBuilderTest extends TestCase
{
    public function testCreateSidebarMenu(): void
    {
        $menu = $this->createBuilder()->createSidebarMenu($this->createUrls(), 'components/avatar');

        $this->assertCount(2, $menu->getChildren());

        $components = $menu->getChildren()[0];
        $this->assertCount(14, $components->getChildren());

        $avatar = $components->getChildren()[0];
        $this->assertEquals('Avatar', $avatar->getLabel());
        $this->assertEquals('/components/avatar?first', $avatar->getUrl());
        $this->assertTrue($avatar->isActive());

        $badge = $components->getChildren()[1];
        $this->assertEquals('Badge', $badge->getLabel());
        $this->assertEquals('/components/badge?first', $badge->getUrl());
        $this->assertFalse($badge->isActive());

        $pages = $menu->getChildren()[1];
        $this->assertCount(4, $pages->getChildren());
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

    public function testFolderHoldingOnlyAFolderIsKept(): void
    {
        $directory = sys_get_temp_dir().'/storia_menu_'.bin2hex(random_bytes(4));
        mkdir($directory.'/components/outer/inner', 0o777, true);
        touch($directory.'/components/outer/inner/one.yaml');
        touch($directory.'/components/outer/inner/two.yaml');

        try {
            $menu = $this->createBuilder(defaultPath: $directory)->createSidebarMenu($this->createUrls(), 'components/outer/inner/two');
        } finally {
            (new Filesystem())->remove($directory);
        }

        $outer = $menu->getChildren()[0]->getChildren()[0];
        $this->assertSame('Outer', $outer->getLabel());
        $this->assertTrue($outer->isOpened());
        $this->assertCount(1, $outer->getChildren());
        $this->assertSame('Inner', $outer->getChildren()[0]->getLabel());
        $this->assertCount(2, $outer->getChildren()[0]->getChildren());
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

    public function testToleratesMissingFolders(): void
    {
        $defaultPath = __DIR__.'/../TestApplication/storia_broken';
        $builder = new MenuBuilder($defaultPath, new ViewBuilder($defaultPath, [], new YamlPreProcessor()));

        $menu = $builder->createSidebarMenu($this->createUrls(), null, ['components/broken' => 'default']);

        $this->assertSame('/components/broken?default', $menu->getChildren()[0]->getChildren()[0]->getUrl());
        $this->assertSame([], $menu->getChildren()[1]->getChildren());
    }

    /**
     * @param ?\Closure(string): View $build
     */
    private function createBuilder(?\Closure $build = null, ?string $defaultPath = null): MenuBuilder
    {
        $build ??= static fn (string $path): View => new View($path, variants: [
            new Variant('first', 'First'),
            new Variant('second', 'Second'),
        ]);

        $builder = new readonly class($build) implements BuilderInterface {
            public function __construct(private \Closure $build)
            {
            }

            public function supports(string $path, array $config): bool
            {
                return true;
            }

            public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View
            {
                return ($this->build)($path);
            }
        };

        $defaultPath ??= __DIR__.'/../TestApplication/storia';

        return new MenuBuilder($defaultPath, new ViewBuilder($defaultPath, [$builder], new YamlPreProcessor()));
    }

    private function createUrls(): LinkGeneratorInterface
    {
        $urls = $this->createMock(LinkGeneratorInterface::class);
        $urls->method('view')->willReturnCallback(static fn (string $path, ?string $variant = null): string => '/'.$path.'?'.$variant);

        return $urls;
    }
}
