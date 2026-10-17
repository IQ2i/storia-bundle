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

class EnabledTest extends WebTestCase
{
    public function testEnabledByDefaultInDebug(): void
    {
        $client = static::createClient(['debug' => true]);
        $client->request('GET', '/storia/components/badge?variant=default');

        $this->assertResponseIsSuccessful();
    }

    public function testDisabledByDefaultWithoutDebug(): void
    {
        $client = static::createClient(['debug' => false]);
        $client->request('GET', '/storia/components/badge?variant=default');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCanBeForcedWithoutDebug(): void
    {
        $client = static::createClient(['environment' => 'forced_enabled', 'debug' => false]);
        $client->request('GET', '/storia/components/badge?variant=default');

        $this->assertResponseIsSuccessful();
    }
}
