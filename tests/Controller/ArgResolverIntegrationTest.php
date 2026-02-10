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

namespace IQ2i\StoriaBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArgResolverIntegrationTest extends WebTestCase
{
    public function testComponentWithStaticMethodArgDefault(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/product?variant=default');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Test Product', $content);
        $this->assertStringContainsString('29.99', $content);
        $this->assertStringContainsString('In Stock', $content);
    }

    public function testComponentWithStaticMethodArgExpensive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/product?variant=expensive');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Premium Product', $content);
        $this->assertStringContainsString('999.99', $content);
        $this->assertStringContainsString('In Stock', $content);
    }

    public function testComponentWithStaticMethodArgOutOfStock(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/product?variant=outOfStock');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Unavailable Product', $content);
        $this->assertStringContainsString('49.99', $content);
        $this->assertStringContainsString('Out of Stock', $content);
    }

    public function testComponentWithStaticArgsStillWorks(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/product?variant=static');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Static Product', $content);
        $this->assertStringContainsString('19.99', $content);
        $this->assertStringContainsString('In Stock', $content);
    }

    public function testIframeViewWithResolvedArgs(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/product?variant=default');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Test Product', $content);
        $this->assertStringContainsString('29.99', $content);
    }
}
