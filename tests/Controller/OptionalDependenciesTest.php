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

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Also run without symfony/form and symfony/ux-twig-component (CI job "phpunit-minimal").
 */
#[Group('no-optional')]
class OptionalDependenciesTest extends WebTestCase
{
    public function testTemplateViewsWorkWithoutOptionalPackages(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/badge?variant=default');

        $this->assertResponseIsSuccessful();

        $client->request('GET', '/storia/iframe/components/badge?variant=default');

        $this->assertResponseIsSuccessful();
    }
}
