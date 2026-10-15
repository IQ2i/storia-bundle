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

class ViewControllerTest extends WebTestCase
{
    public function testComponent(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/badge?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertCount(4, $crawler->filter('header > .tabs > .tabs__item'));
        $this->assertCount(2, $crawler->filter('main > div > div > .tabs > .tabs__item'));
    }

    public function testViewWithoutVariantRedirectsToTheFirstOne(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/badge');

        $this->assertResponseRedirects('/storia/components/badge?variant=default');
    }

    public function testLinksAreGeneratedFromTheRouter(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/badge?variant=large');

        $this->assertSame('/storia/iframe/components/badge?variant=large', $crawler->filter('iframe')->attr('src'));
        $this->assertSame('/storia/components/badge?variant=default', $crawler->filter('header .tabs__item_link')->first()->attr('href'));
        $this->assertCount(1, $crawler->filter('header .tab-is-active'));
        $this->assertSelectorTextContains('header .tab-is-active', 'Large');
        $this->assertSame('/storia/components/avatar?variant=rounded', $crawler->filter('.menu_item a')->first()->attr('href'));
        $this->assertSelectorExists('.menu_item.active a[href="/storia/components/badge?variant=default"]');
        $this->assertSelectorExists('[data-controller~="live-reload"]');
        $this->assertSelectorExists('script[type="importmap"]');
        $this->assertSelectorNotExists('.main__sidebar_notice');
    }

    public function testUnknownView(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/unknown?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('main');
        $this->assertEquals('', $crawler->filter('.main__inner')->innerText());
    }
}
