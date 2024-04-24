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

use Tempest\Highlight\Highlighter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HighlightExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('iq2i_storia_highlight', [$this, 'highlight']),
        ];
    }

    public function highlight(string $content, string $language): string
    {
        return (new Highlighter())->parse($content, $language);
    }
}
