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

namespace IQ2i\StoriaBundle\Controller;

use IQ2i\StoriaBundle\Menu\MenuBuilder;
use IQ2i\StoriaBundle\Url\LinkGeneratorInterface;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\ViewBuilder;
use IQ2i\StoriaBundle\View\ViewRenderer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
final readonly class ViewController
{
    public function __construct(
        private ViewBuilder $viewBuilder,
        private ViewRenderer $viewRenderer,
        private MenuBuilder $menuBuilder,
        private LinkGeneratorInterface $urls,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $view = $this->viewBuilder->createFromRequest($request);

        if (null !== $view && null === $request->query->get('variant')) {
            /** @var Variant $variant */
            $variant = current($view->getVariants());

            return new RedirectResponse($this->urls->view($view->getPath(), $variant->getPath()));
        }

        $menu = $this->menuBuilder->createSidebarMenu($this->urls, $request->attributes->get('view'));

        return new Response($this->viewRenderer->renderShell($view, $menu->getChildren(), $this->urls));
    }
}
