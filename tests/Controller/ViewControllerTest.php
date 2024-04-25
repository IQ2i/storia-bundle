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
        $this->assertCount(3, $crawler->filter('main > div > div > .tabs > .tabs__item'));
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
