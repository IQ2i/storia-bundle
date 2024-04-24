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

use IQ2i\StoriaBundle\View\ViewBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class ViewController
{
    public function __construct(
        private ViewBuilder $viewBuilder,
        private Environment $twig,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $view = $this->viewBuilder->createFromRequest($request);

        if (null !== $view && null === $view->getCurrentVariant()) {
            return new RedirectResponse($this->router->generate('iq2i_storia_view', [
                'view' => $view,
                'variant' => $view->getFirstVariant(),
            ]));
        }

        return new Response($this->twig->render('@IQ2iStoria/view.html.twig', [
            'view' => $view,
        ]));
    }
}
