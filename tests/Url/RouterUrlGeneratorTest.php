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

namespace IQ2i\StoriaBundle\Tests\Url;

use IQ2i\StoriaBundle\Url\RouterUrlGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\RouterInterface;

class RouterUrlGeneratorTest extends TestCase
{
    public function testGeneratesUrlsFromTheRouter(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnCallback(static fn (string $name, array $parameters): string => $name.':'.json_encode($parameters));

        $packages = $this->createMock(Packages::class);
        $packages->method('getUrl')->willReturnCallback(static fn (string $path): string => '/base'.$path);

        $urls = new RouterUrlGenerator($router, $packages);

        $this->assertSame('iq2i_storia_view:{"view":"components\/badge","variant":"default"}', $urls->view('components/badge', 'default'));
        $this->assertSame('iq2i_storia_view:{"view":"components\/badge"}', $urls->view('components/badge'));
        $this->assertSame('iq2i_storia_iframe:{"view":"components\/badge","variant":"default"}', $urls->iframe('components/badge', 'default'));
        $this->assertSame('/base/bundles/iq2istoria/storia.css', $urls->asset('storia.css'));
    }
}
