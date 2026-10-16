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

use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

class Toast
{
    public string $message = '';

    #[ExposeInTemplate('label')]
    private string $computed = 'x';

    /**
     * Mounts the toast.
     *
     * @param string $message The text to display
     * @param int    $delay   Seconds before it closes @control range min=1 max=30
     */
    public function mount(string $message, int $delay = 5, ?\DateTimeImmutable $at = null): void
    {
        $this->message = $message.$delay.$this->computed;
    }

    #[ExposeInTemplate]
    public function getSummary(): string
    {
        return $this->message;
    }

    #[ExposeInTemplate(name: 'isUrgent')]
    public function urgent(): bool
    {
        return false;
    }
}
