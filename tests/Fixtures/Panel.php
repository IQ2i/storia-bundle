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

namespace IQ2i\StoriaBundle\Tests\Fixtures;

class Panel
{
    /**
     * Title of the panel.
     *
     * @control textarea
     */
    public string $title = 'Panel';

    public int $rows = 2;

    public bool $closable = false;

    public PanelSize $size = PanelSize::Small;

    public array $items = [];

    public ?\DateTimeInterface $date = null;

    public string $required;

    public static string $ignored = 'static';
}
