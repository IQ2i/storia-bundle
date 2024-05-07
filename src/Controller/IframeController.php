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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;

final readonly class IframeController
{
    public function __construct(
        private ViewBuilder $viewBuilder,
        private Environment $twig,
    ) {
    }

    public function __invoke(Request $request, ?Profiler $profiler): Response
    {
        if (null !== $profiler) {
            $profiler->disable();
        }

        $view = $this->viewBuilder->createFromRequest($request);
        if (null === $view) {
            throw new NotFoundHttpException();
        }

        $content = $view->getHtmlContent();

        $pos = strripos((string) $content, '</body>');
        if (false !== $pos) {
            return new Response($content);
        }

        return new Response($this->twig->render('@IQ2iStoria/iframe.html.twig', [
            'content' => $content,
        ]));
    }
}
