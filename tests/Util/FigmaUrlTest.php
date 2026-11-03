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

namespace IQ2i\StoriaBundle\Tests\Util;

use IQ2i\StoriaBundle\Util\FigmaUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FigmaUrlTest extends TestCase
{
    #[DataProvider('provideUrls')]
    public function testIsValid(string $url, bool $expected): void
    {
        $this->assertSame($expected, FigmaUrl::isValid($url));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function provideUrls(): iterable
    {
        yield 'design file' => ['https://www.figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar', true];
        yield 'frame' => ['https://www.figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar?node-id=1-2&t=abc', true];
        yield 'prototype' => ['https://www.figma.com/proto/AbCdEfGhIjKlMnOpQrStUv/Avatar?node-id=1-2', true];
        yield 'board' => ['https://www.figma.com/board/AbCdEfGhIjKlMnOpQrStUv', true];
        yield 'bare host' => ['https://figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar', true];
        yield 'embed host' => ['https://embed.figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar', true];
        yield 'http' => ['http://www.figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar', false];
        yield 'other site' => ['https://example.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar', false];
        yield 'look-alike host' => ['https://www.figma.com.evil.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar', false];
        yield 'short key' => ['https://www.figma.com/design/AbCdEf/Avatar', false];
        yield 'no key' => ['https://www.figma.com/files', false];
    }

    public function testToEmbedUrl(): void
    {
        $this->assertSame(
            'https://embed.figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar?node-id=1-2&viewport-controls=false&embed-host=ui-storia',
            FigmaUrl::toEmbedUrl('https://www.figma.com/design/AbCdEfGhIjKlMnOpQrStUv/Avatar?node-id=1-2&embed_origin=https://x&viewport_controls=false&embed-host=other#top')
        );
        $this->assertSame(
            'https://embed.figma.com/proto/AbCdEfGhIjKlMnOpQrStUv/Avatar?embed-host=ui-storia',
            FigmaUrl::toEmbedUrl('https://figma.com/proto/AbCdEfGhIjKlMnOpQrStUv/Avatar')
        );
    }

    public function testToEmbedUrlRejectsOtherUrls(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"https://example.com/x" is not a Figma URL.');

        FigmaUrl::toEmbedUrl('https://example.com/x');
    }
}
