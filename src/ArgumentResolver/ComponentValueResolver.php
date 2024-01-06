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

namespace IQ2i\ArquiBundle\ArgumentResolver;

use IQ2i\ArquiBundle\Dto\Component;
use IQ2i\ArquiBundle\Factory\ComponentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

readonly class ComponentValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ComponentFactory $componentFactory,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Component::class !== $argument->getType()) {
            return [];
        }

        return [$this->componentFactory->createFromRequest($request)];
    }
}
