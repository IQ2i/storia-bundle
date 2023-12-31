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

namespace IQ2i\ArquiBundle\Twig;

use IQ2i\ArquiBundle\Dto\Menu;
use IQ2i\ArquiBundle\Dto\MenuItem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function Symfony\Component\String\u;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $defaultPath,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('arqui_menu', $this->getMenu(...)),
        ];
    }

    public function getMenu(): Menu
    {
        $menu = new Menu();
        $this->getMenuChildren($menu, $this->defaultPath);

        return $menu;
    }

    public function getMenuChildren(Menu $menu, string $path): void
    {
        $opened = false;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($path)->depth('== 0')->sortByName(true)->sortByType() as $file) {
            if ($file->isDir()) {
                $label = u($file->getBasename())->title()->toString();
                $child = new Menu($label);
                $this->getMenuChildren($child, $file->getPathname());
            } else {
                $label = u($file->getFilenameWithoutExtension())->replace('.html', '')->title()->toString();
                $componentPath = u($file->getPathname())->replace($this->defaultPath, '')->trim('/')->toString();
                $path = $this->router->generate('iq2i_arqui_story', ['component' => $componentPath]);

                $urlParts = parse_url($this->requestStack->getCurrentRequest()->getRequestUri());
                $isActive = isset($urlParts['path']) && str_ends_with($path, $urlParts['path']);
                $opened = $opened || $isActive;

                $child = new MenuItem($label, $path, $isActive);
            }

            $menu->addChild($child);
        }

        $menu->setOpened($opened);
    }
}
