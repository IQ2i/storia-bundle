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

use IQ2i\ArquiBundle\Dto\Component;
use IQ2i\ArquiBundle\Dto\Variant;
use IQ2i\ArquiBundle\Factory\MenuFactory;
use IQ2i\ArquiBundle\Registry\ComponentRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class StoryController
{
    public function __construct(
        private MenuFactory $menuFactory,
        private Environment $twig,
        private RouterInterface $router,
        private ComponentRegistry $componentRegistry,
    ) {
    }

    public function __invoke(Request $request, Component $component = null): Response
    {
        if (!$component instanceof Component) {
            return new Response($this->twig->render('@IQ2iArqui/story.html.twig', [
                'menu' => $this->menuFactory->createSidebarMenu($request),
                'component' => null,
            ]));
        }

        if (!$component->getVariant() instanceof Variant) {
            return new RedirectResponse($this->router->generate('iq2i_arqui_story', [
                'component' => $component->getPath(),
                'variant' => $this->componentRegistry->findFirstComponentVariant($component),
            ]));
        }

        return new Response($this->twig->render('@IQ2iArqui/story.html.twig', [
            'menu' => $this->menuFactory->createSidebarMenu($request),
            'tabs' => $this->menuFactory->createTabMenu($request, $component),
            'component' => $component,
        ]));
    }
}
