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

use IQ2i\StoriaBundle\Dto\Component;
use IQ2i\StoriaBundle\Dto\Variant;
use IQ2i\StoriaBundle\Factory\MenuFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class ComponentController
{
    public function __construct(
        private MenuFactory $menuFactory,
        private Environment $twig,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, ?Component $component = null): Response
    {
        if (!$component instanceof Component) {
            return new Response($this->twig->render('@IQ2iStoria/view/component.html.twig', [
                'menu' => $this->menuFactory->createSidebarMenu($request),
                'component' => null,
            ]));
        }

        if (!$component->getCurrentVariant() instanceof Variant) {
            return new RedirectResponse($this->router->generate('iq2i_storia_view', [
                'component' => $component,
                'variant' => $component->getFirstVariant(),
            ]));
        }

        return new Response($this->twig->render('@IQ2iStoria/view/component.html.twig', [
            'menu' => $this->menuFactory->createSidebarMenu($request),
            'component' => $component,
        ]));
    }
}
