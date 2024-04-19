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

namespace IQ2i\StoriaBundle\Twig;

use IQ2i\StoriaBundle\Menu\MenuBuilder;
use Symfony\Bridge\Twig\AppVariable;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly MenuBuilder $menuBuilder,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('iq2i_storia_menu', [$this, 'getMenu'], ['needs_context' => true]),
        ];
    }

    public function getMenu(array $context): array
    {
        /** @var AppVariable $appVariable */
        $appVariable = $context['app'];
        $request = $appVariable->getRequest();

        $menu = $this->menuBuilder->createSidebarMenu($request);

        return $menu->getChildren();
    }
}
