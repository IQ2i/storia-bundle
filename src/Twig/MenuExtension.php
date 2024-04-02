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

namespace IQ2i\StoriaBundle\Twig;

use IQ2i\StoriaBundle\Twig\Dto\Menu;
use IQ2i\StoriaBundle\Twig\Dto\MenuItem;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function Symfony\Component\String\u;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $defaultPath,
        private readonly RouterInterface $router,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('iq2i_storia_menu', [$this, 'getMenu'], ['needs_context' => true]),
        ];
    }

    public function getMenu(array $context): array
    {
        /** @var AppVariable $appVariable */
        $appVariable = $context['app'];
        $request = $appVariable->getRequest();

        $menu = new Menu();

        $components = new Menu('Components');
        $this->getChildren($request, $components, 'components');
        $menu->addChild($components);

        $pages = new Menu('Pages');
        $this->getChildren($request, $pages, 'pages');
        $menu->addChild($pages);

        return $menu->getChildren();
    }

    private function getChildren(Request $request, Menu $menu, string $folder): void
    {
        $opened = false;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($this->defaultPath.'/'.$folder)->depth('== 0')->sortByName(true)->sortByType() as $file) {
            $label = u($file->getFilenameWithoutExtension())->title()->toString();

            if ($file->isDir()) {
                $child = new Menu($label);
                $this->getChildren($request, $child, $folder.'/'.$file->getBasename());
                if ([] === $child->getChildren()) {
                    continue;
                }

                $opened = $opened || $child->isOpened();

                if (1 === \count($child->getChildren())) {
                    $menuItem = $child->getChildren()[0];
                    $opened = $opened || $menuItem->isActive();
                    $child = new MenuItem($label, $menuItem->getUrl(), $menuItem->isActive());
                }
            } else {
                if ('yaml' !== $file->getExtension()) {
                    continue;
                }

                $path = $this->router->generate('iq2i_storia_view', [
                    'component' => u($file->getPathname())->replace($this->defaultPath.'/', '')->trimSuffix('.yaml')->toString(),
                ]);

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
