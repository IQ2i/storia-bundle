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

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class ViewController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function __invoke(?string $component): Response
    {
        return new Response($this->twig->render('@IQ2iArqui/view/view.html.twig'));
    }
}
