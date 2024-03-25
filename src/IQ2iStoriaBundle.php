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

namespace IQ2i\StoriaBundle;

use IQ2i\StoriaBundle\DependencyInjection\IQ2iStoriaExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class IQ2iStoriaBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new IQ2iStoriaExtension();
    }
}
