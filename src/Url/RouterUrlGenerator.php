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

namespace IQ2i\StoriaBundle\Url;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;

readonly class RouterUrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private RouterInterface $router,
        private Packages $packages,
    ) {
    }

    public function view(string $path, ?string $variant = null): string
    {
        return $this->router->generate('iq2i_storia_view', array_filter([
            'view' => $path,
            'variant' => $variant,
        ], static fn (?string $value): bool => null !== $value));
    }

    public function iframe(string $path, ?string $variant = null): string
    {
        return $this->router->generate('iq2i_storia_iframe', array_filter([
            'view' => $path,
            'variant' => $variant,
        ], static fn (?string $value): bool => null !== $value));
    }

    public function asset(string $path): string
    {
        return $this->packages->getUrl('/bundles/iq2istoria/'.$path);
    }
}
