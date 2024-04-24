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

namespace IQ2i\StoriaBundle\Tests\Menu;

use IQ2i\StoriaBundle\Menu\MenuBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class MenuBuilderTest extends TestCase
{
    public function testCreateSidebarMenu(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(static fn (string $name, array $parameters = []): string => '/'.$parameters['view']);

        $builder = new MenuBuilder(\dirname(__DIR__).'/TestApplication/storia', $router);
        $menu = $builder->createSidebarMenu(Request::create('/components/avatar'));

        $this->assertCount(2, $menu->getChildren());

        $components = $menu->getChildren()[0];
        $this->assertCount(4, $components->getChildren());

        $avatar = $components->getChildren()[0];
        $this->assertEquals('Avatar', $avatar->getLabel());
        $this->assertEquals('/components/avatar', $avatar->getUrl());
        $this->assertTrue($avatar->isActive());

        $badge = $components->getChildren()[1];
        $this->assertEquals('Badge', $badge->getLabel());
        $this->assertEquals('/components/badge', $badge->getUrl());
        $this->assertFalse($badge->isActive());

        $pages = $menu->getChildren()[1];
        $this->assertCount(1, $pages->getChildren());
    }
}
