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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class IframeController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function __invoke(Request $request, string $component): Response
    {
        return new Response($this->twig->render('@IQ2iArqui/iframe.html.twig', [
            'component' => $component,
            'variant' => $request->query->get('variant'),
        ]));
    }
}
