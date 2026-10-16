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

use IQ2i\StoriaBundle\Url\StaticLinkGenerator;
use PHPUnit\Framework\TestCase;

class StaticLinkGeneratorTest extends TestCase
{
    public function testLinksFromTheRoot(): void
    {
        $urls = new StaticLinkGenerator();

        $this->assertSame('components/badge/default.html', $urls->view('components/badge', 'default'));
        $this->assertSame('iframe/components/badge/default.html', $urls->iframe('components/badge', 'default'));
        $this->assertSame('bundles/iq2istoria/storia.css', $urls->asset('storia.css'));
    }

    public function testLinksAreRelativeToTheCurrentFileDepth(): void
    {
        $urls = new StaticLinkGenerator('components/badge/default.html');

        $this->assertSame('../../components/button/button/large.html', $urls->view('components/button/button', 'large'));
        $this->assertSame('../../iframe/components/badge/default.html', $urls->iframe('components/badge', 'default'));
        $this->assertSame('../../bundles/iq2istoria/storia.css', $urls->asset('storia.css'));
    }

    public function testSegmentsAreUrlEncoded(): void
    {
        $this->assertSame('components/my%20badge/with%20space.html', (new StaticLinkGenerator())->view('components/my badge', 'with space'));
    }

    public function testViewWithoutVariantCannotBeExported(): void
    {
        $this->expectException(\LogicException::class);

        (new StaticLinkGenerator())->view('components/badge');
    }

    public function testIframeWithoutVariantCannotBeExported(): void
    {
        $this->expectException(\LogicException::class);

        (new StaticLinkGenerator())->iframe('components/badge');
    }
}
