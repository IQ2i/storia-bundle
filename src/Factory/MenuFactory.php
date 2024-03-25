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

namespace IQ2i\StoriaBundle\Factory;

use IQ2i\StoriaBundle\Dto\Menu;
use IQ2i\StoriaBundle\Dto\MenuItem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

use function Symfony\Component\String\u;

readonly class MenuFactory
{
    public function __construct(
        private string $defaultPath,
        private RouterInterface $router,
    ) {
    }

    /**
     * @return array<Menu|MenuItem>
     */
    public function createSidebarMenu(Request $request): array
    {
        $menu = new Menu();

        $components = new Menu('Components');
        $this->getMenu($request, $components, $this->defaultPath.'/components');
        $menu->addChild($components);

        $pages = new Menu('Pages');
        $this->getMenu($request, $pages, $this->defaultPath.'/pages');
        $menu->addChild($pages);

        return $menu->getChildren();
    }

    private function getMenu(Request $request, Menu $menu, string $path): void
    {
        $opened = false;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($path)->depth('== 0')->sortByName(true)->sortByType() as $file) {
            if ($file->isDir()) {
                $label = u($file->getBasename())->title()->toString();
                $child = new Menu($label);
                $this->getMenu($request, $child, $file->getPathname());
                $opened = $opened || $child->isOpened();

                if ([] === $child->getChildren()) {
                    continue;
                }

                if (1 === \count($child->getChildren())) {
                    $menuItem = $child->getChildren()[0];
                    $opened = $opened || $menuItem->isActive();
                    $child = new MenuItem($label, $menuItem->getUrl(), $menuItem->isActive());
                }
            } else {
                if ('yaml' !== $file->getExtension()) {
                    continue;
                }

                $label = u($file->getFilenameWithoutExtension())->title()->toString();
                $componentPath = u($file->getPathname())->replace($this->defaultPath, '')->trim('/')->toString();
                $path = $this->router->generate('iq2i_storia_story', ['component' => $componentPath]);

                $urlParts = parse_url($request->getRequestUri());
                $isActive = isset($urlParts['path']) && str_ends_with($path, $urlParts['path']);
                $opened = $opened || $isActive;

                $child = new MenuItem($label, $path, $isActive);
            }

            $menu->addChild($child);
        }

        $menu->setOpened($opened);
        $menu->reorderChildren();
    }
}
