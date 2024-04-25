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

    public function testUnknownView(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/unknown?variant=default');

        $this->assertResponseStatusCodeSame(404);
    }
}
