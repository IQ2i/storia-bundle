<?php

/*
 * This file is part of the Arqui project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\ArquiBundle\Factory;

use IQ2i\ArquiBundle\Dto\Component;
use IQ2i\ArquiBundle\Dto\Menu;
use IQ2i\ArquiBundle\Dto\MenuItem;
use IQ2i\ArquiBundle\Registry\ComponentRegistry;
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
        private ComponentRegistry $componentRegistry,
    ) {
    }

    /**
     * @return array<Menu|MenuItem>
     */
    public function createSidebarMenu(Request $request): array
    {
        $menu = new Menu();
        $this->getMenuChildren($request, $menu, $this->defaultPath);

        return $menu->getChildren();
    }

    /**
     * @return array<MenuItem>
     */
    public function createTabMenu(Request $request, Component $component): array
    {
        $tabs = new Menu();
        foreach ($this->componentRegistry->findComponentVariants($component) as $variant) {
            $tabs->addChild(new MenuItem(
                $variant->getName(),
                $this->router->generate('iq2i_arqui_story', ['component' => $component->getPath(), 'variant' => $variant->getPath()]),
                $variant->getPath() === $request->query->get('variant'),
            ));
        }

        return $tabs->getChildren();
    }

    private function getMenuChildren(Request $request, Menu $menu, string $path): void
    {
        $opened = false;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($path)->depth('== 0')->sortByName(true)->sortByType() as $file) {
            if ($file->isDir()) {
                $label = u($file->getBasename())->title()->toString();
                $child = new Menu($label);
                $this->getMenuChildren($request, $child, $file->getPathname());
            } else {
                $label = u($file->getFilenameWithoutExtension())->replace('.html', '')->title()->toString();
                $componentPath = u($file->getPathname())->replace($this->defaultPath, '')->trim('/')->toString();
                $path = $this->router->generate('iq2i_arqui_story', ['component' => $componentPath]);

                $urlParts = parse_url($request->getRequestUri());
                $isActive = isset($urlParts['path']) && str_ends_with($path, $urlParts['path']);
                $opened = $opened || $isActive;

                $child = new MenuItem($label, $path, $isActive);
            }

            $menu->addChild($child);
        }

        $menu->setOpened($opened);
    }
}
