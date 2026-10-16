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

namespace IQ2i\StoriaBundle\View;

use IQ2i\StoriaBundle\Menu\Dto\Menu;
use IQ2i\StoriaBundle\Menu\Dto\MenuItem;
use IQ2i\StoriaBundle\Url\LinkGeneratorInterface;
use IQ2i\StoriaBundle\View\Dto\View;
use Twig\Environment;

readonly class ViewRenderer
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * @param list<Menu|MenuItem> $menu
     */
    public function renderShell(?View $view, array $menu, LinkGeneratorInterface $urls, ?\DateTimeImmutable $exportedAt = null): string
    {
        return $this->twig->render('@IQ2iStoria/view.html.twig', [
            'view' => $view,
            'menu' => $menu,
            'urls' => $urls,
            'static' => null !== $exportedAt,
            'exported_at' => $exportedAt,
        ]);
    }

    public function renderIframe(View $view): string
    {
        $content = $view->getHtmlContent();

        if (false !== stripos((string) $content, '</body>')) {
            return $content;
        }

        return $this->twig->render('@IQ2iStoria/iframe.html.twig', [
            'content' => $content,
        ]);
    }
}
