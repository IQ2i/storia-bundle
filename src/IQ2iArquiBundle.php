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

namespace IQ2i\ArquiBundle;

use IQ2i\ArquiBundle\DependencyInjection\IQ2iArquiExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class IQ2iArquiBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new IQ2IArquiExtension();
    }
}
