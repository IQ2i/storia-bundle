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

namespace IQ2i\StoriaBundle\Menu;

use IQ2i\StoriaBundle\Menu\Dto\Menu;
use IQ2i\StoriaBundle\Menu\Dto\MenuItem;
use IQ2i\StoriaBundle\Url\UrlGeneratorInterface;
use IQ2i\StoriaBundle\View\ViewBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use function Symfony\Component\String\u;

readonly class MenuBuilder
{
    public function __construct(
        private string $defaultPath,
        private ViewBuilder $viewBuilder,
    ) {
    }

    /**
     * @param ?array<string, ?string> $firstVariants
     */
    public function createSidebarMenu(UrlGeneratorInterface $urls, ?string $currentPath, ?array $firstVariants = null): Menu
    {
        $menu = new Menu();

        $components = new Menu('Components');
        $this->getChildren($urls, $currentPath, $firstVariants, $components, 'components');
        $menu->addChild($components);

        $pages = new Menu('Pages');
        $this->getChildren($urls, $currentPath, $firstVariants, $pages, 'pages');
        $menu->addChild($pages);

        return $menu;
    }

    private function getChildren(UrlGeneratorInterface $urls, ?string $currentPath, ?array $firstVariants, Menu $menu, string $folder): void
    {
        if (!is_dir($this->defaultPath.'/'.$folder)) {
            return;
        }

        $opened = false;

        /** @var SplFileInfo $file */
        foreach ((new Finder())->in($this->defaultPath.'/'.$folder)->depth('== 0')->sortByName(true)->sortByType() as $file) {
            $label = u($file->getFilenameWithoutExtension())->title()->toString();
            $label = ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $label))));

            if ($file->isDir()) {
                $child = new Menu($label);
                $this->getChildren($urls, $currentPath, $firstVariants, $child, $folder.'/'.$file->getBasename());
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
                if (!\in_array($file->getExtension(), ['yaml', 'yml'], true)) {
                    continue;
                }

                $viewPath = u($file->getPathname())->replace($this->defaultPath.'/', '')->trimSuffix('.'.$file->getExtension())->toString();
                $isActive = $viewPath === $currentPath;
                $opened = $opened || $isActive;

                $path = $urls->view($viewPath, $this->firstVariant($viewPath, $firstVariants));
                $child = new MenuItem($label, $path, $isActive);
            }

            $menu->addChild($child);
        }

        $menu->setOpened($opened);
        $menu->reorderChildren();
    }

    /**
     * @param ?array<string, ?string> $firstVariants
     */
    private function firstVariant(string $viewPath, ?array $firstVariants): ?string
    {
        if (null !== $firstVariants) {
            return $firstVariants[$viewPath] ?? null;
        }

        // A broken view must not take the whole menu down: the link then targets the view without variant.
        try {
            $variants = $this->viewBuilder->create($viewPath, null)?->getVariants() ?? [];
        } catch (\Throwable) {
            return null;
        }

        return [] === $variants ? null : current($variants)->getPath();
    }
}
