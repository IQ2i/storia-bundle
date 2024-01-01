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

namespace IQ2i\ArquiBundle\Controller;

use IQ2i\ArquiBundle\Dto\Menu;
use IQ2i\ArquiBundle\Dto\MenuItem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

use function Symfony\Component\String\u;

final readonly class StoryController
{
    public function __construct(
        private string $defaultPath,
        private Environment $twig,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, ?string $component): Response
    {
        if (null === $component) {
            return new Response($this->twig->render('@IQ2iArqui/story.html.twig', [
                'menu' => $this->getMenu($request)->getChildren(),
            ]));
        }

        $selectedVariant = $request->query->get('variant');
        if (null === $selectedVariant) {
            $blocks = $this->twig->load($component)->getBlockNames();
            $selectedVariant = array_shift($blocks);

            return new RedirectResponse($this->router->generate('iq2i_arqui_story', ['component' => $component, 'variant' => $selectedVariant]));
        }

        $content = file_get_contents($this->defaultPath.'/'.$component);
        preg_match('/{% block '.$selectedVariant.' %}((?!{% endblock '.$selectedVariant.' %}).*){% endblock '.$selectedVariant.' %}/s', $content, $matches);
        $twig = $matches[1] ?? null;

        return new Response($this->twig->render('@IQ2iArqui/story.html.twig', [
            'menu' => $this->getMenu($request)->getChildren(),
            'tabs' => $this->getTabs($request, $component)->getChildren(),
            'component' => $component,
            'variant' => $selectedVariant,
            'twig' => $this->cleanSource($twig, true),
            'html' => $this->cleanSource($this->twig->load($component)->renderBlock($selectedVariant)),
        ]));
    }

    private function getMenu(Request $request): Menu
    {
        $menu = new Menu();
        $this->getMenuChildren($request, $menu, $this->defaultPath);

        return $menu;
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

    private function getTabs(Request $request, string $component): Menu
    {
        $tabs = new Menu();
        foreach ($this->twig->load($component)->getBlockNames() as $block) {
            $tabs->addChild(new MenuItem(
                ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $block)))),
                $this->router->generate('iq2i_arqui_story', ['component' => $component, 'variant' => $block]),
                $block === $request->query->get('variant'),
            ));
        }

        return $tabs;
    }

    private function cleanSource(string $source, bool $unindent = false): string
    {
        $lines = explode("\n", $source);
        if ($unindent) {
            $lines = array_map(static fn (string $line): string => substr($line, 4), $lines);
        }

        $source = u(implode("\n", $lines));

        return $source->trim()->toString();
    }
}
