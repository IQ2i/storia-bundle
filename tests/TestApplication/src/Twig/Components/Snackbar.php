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

namespace IQ2i\StoriaBundle\Tests\TestApplication\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Snackbar', exposePublicProps: false)]
class Snackbar
{
    public string $message = '';

    public int $delay = 5;

    /**
     * @param int $delay Seconds before the snackbar closes @control range min=1 max=30
     */
    public function mount(string $message, int $delay = 5): void
    {
        $this->message = strtoupper($message);
        $this->delay = $delay;
    }
}
