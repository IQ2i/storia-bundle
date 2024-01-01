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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final readonly class StoryController
{
    public function __construct(
        private string $defautlPath,
        private Environment $twig,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, ?string $component): Response
    {
        $tabs = new Menu();
        if (null === $component) {
            return new Response($this->twig->render('@IQ2iArqui/story.html.twig', [
                'tabs' => $tabs->getChildren(),
                'view' => null,
            ]));
        }

        $selectedVariant = $request->query->get('variant');
        foreach ($this->twig->load($component)->getBlockNames() as $block) {
            if (null === $selectedVariant) {
                $selectedVariant = $block;
            }

            $tabs->addChild(new MenuItem(
                ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $block)))),
                $this->router->generate('iq2i_arqui_story', ['component' => $component, 'variant' => $block]),
                $selectedVariant === $block,
            ));
        }

        $content = file_get_contents($this->defautlPath.'/'.$component);
        preg_match('/{% block '.$selectedVariant.' %}((?!{% endblock %na'.$selectedVariant.'me %}).*){% endblock '.$selectedVariant.' %}/s', $content, $matches);

        $verbatim = $matches[1] ?? null;

        return new Response($this->twig->render('@IQ2iArqui/story.html.twig', [
            'tabs' => $tabs->getChildren(),
            'component' => $component,
            'variant' => $selectedVariant,
            'view' => $this->twig->load($component)->renderBlock($selectedVariant),
            'verbatim' => $verbatim,
        ]));
    }
}
