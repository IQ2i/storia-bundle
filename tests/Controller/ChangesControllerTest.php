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

use IQ2i\StoriaBundle\Controller\ChangesController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChangesControllerTest extends TestCase
{
    public function testResponseIsStreamed(): void
    {
        $controller = new ChangesController(\dirname(__DIR__, 2));
        $response = $controller(Request::create('/changes'), null);

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    public function testResponseHeaders(): void
    {
        $controller = new ChangesController(\dirname(__DIR__, 2));
        $response = $controller(Request::create('/changes'), null);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertSame('no-cache, private', $response->headers->get('Cache-Control'));
        $this->assertSame('no', $response->headers->get('X-Accel-Buffering'));
    }
}
