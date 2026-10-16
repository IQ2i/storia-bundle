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

class IframeControllerTest extends WebTestCase
{
    public function testComponent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/badge?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'UI Storia');
        $this->assertSelectorTextContains('body > span', 'Default');
    }

    public function testPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/pages/homepage/homepage?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'My website');
        $this->assertSelectorTextContains('body > p', 'This is the homepage hero');
    }

    public function testSharedMenuFeedsComponentAndPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/storia/iframe/components/header?variant=default');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('nav', 'Home');
        $this->assertSelectorTextContains('nav', 'Shop');

        $client->request('GET', '/storia/iframe/pages/home/home?variant=default');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('nav', 'Home');
        $this->assertSelectorTextContains('nav', 'Shop');
        $this->assertCount(4, $client->getCrawler()->filter('ul.products li'));
        $this->assertSelectorTextContains('ul.products', 'Product 4');
    }

    public function testBlockUsingLoremTag(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/button/button?variant=lorem');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'lorem ipsum dolor');
    }

    public function testFormOptionsUsingDataTag(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/form/choice?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('select', 'In Stock');
        $this->assertSelectorTextContains('select', 'Out of Stock');
    }

    public function testUnknownView(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/unknown?variant=default');

        $this->assertResponseStatusCodeSame(404);
    }
}
