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

namespace IQ2i\StoriaBundle\Tests\Export;

use IQ2i\StoriaBundle\Export\UrlRewriter;
use PHPUnit\Framework\TestCase;

class UrlRewriterTest extends TestCase
{
    private UrlRewriter $rewriter;

    protected function setUp(): void
    {
        $this->rewriter = new UrlRewriter();
    }

    public function testPrefixFor(): void
    {
        $this->assertSame('', UrlRewriter::prefixFor('index.html'));
        $this->assertSame('../../', UrlRewriter::prefixFor('components/badge/default.html'));
        $this->assertSame('../../../', UrlRewriter::prefixFor('iframe/components/badge/default.html'));
    }

    public function testRewritesAssetAttributes(): void
    {
        $html = <<<'HTML'
            <link rel="stylesheet" href="/css/app.css">
            <link rel="icon" href='/favicon.ico'>
            <script src="/js/app.js"></script>
            <img class="logo" src="/img/logo.png" alt="Logo">
            <video poster="/img/poster.jpg"><source src="/video.mp4"></video>
            <object data="/doc.svg"></object>
            HTML;

        $expected = <<<'HTML'
            <link rel="stylesheet" href="../../css/app.css">
            <link rel="icon" href='../../favicon.ico'>
            <script src="../../js/app.js"></script>
            <img class="logo" src="../../img/logo.png" alt="Logo">
            <video poster="../../img/poster.jpg"><source src="../../video.mp4"></video>
            <object data="../../doc.svg"></object>
            HTML;

        $this->assertSame($expected, $this->rewriter->rewriteHtml($html, '../../'));
    }

    public function testRewritesSrcset(): void
    {
        $this->assertSame(
            '<img srcset="../a.png 1x, ../b.png 2x, https://cdn.test/c.png 3x" src="../a.png">',
            $this->rewriter->rewriteHtml('<img srcset="/a.png 1x, /b.png 2x, https://cdn.test/c.png 3x" src="/a.png">', '../')
        );
    }

    public function testLeavesExternalAndPageUrlsUntouched(): void
    {
        $html = <<<'HTML'
            <script src="//cdn.test/app.js"></script>
            <script src="https://cdn.test/app.js"></script>
            <img src="data:image/png;base64,AAAA">
            <img src="relative.png">
            <a href="/about">About</a>
            <form action="/search"></form>
            <link rel="canonical" href="/page">
            <p data="/not-an-asset" style="color: red">Text</p>
            HTML;

        $this->assertSame($html, $this->rewriter->rewriteHtml($html, '../'));
    }

    public function testRewritesInlineStylesAndStyleBlocks(): void
    {
        $html = '<div style="background: url(/img/a.png)"></div><style>.a { background: url("/img/b.png"); } @import "/css/c.css";</style>';

        $this->assertSame(
            '<div style="background: url(../img/a.png)"></div><style>.a { background: url("../img/b.png"); } @import "../css/c.css";</style>',
            $this->rewriter->rewriteHtml($html, '../')
        );
    }

    public function testRewritesImportMap(): void
    {
        $html = '<script type="importmap">{"imports":{"app":"/assets/app-123.js","lib":"https://cdn.test/lib.js"}}</script>';

        $this->assertSame(
            '<script type="importmap">{"imports":{"app":"../assets/app-123.js","lib":"https://cdn.test/lib.js"}}</script>',
            $this->rewriter->rewriteHtml($html, '../')
        );
    }

    public function testRewritesFromTheRoot(): void
    {
        $this->assertSame('<img src="img/a.png"><link rel="icon" href="./">', $this->rewriter->rewriteHtml('<img src="/img/a.png"><link rel="icon" href="/">', ''));
    }

    public function testRewritesCss(): void
    {
        $css = '@import "/css/a.css"; @import url(/css/b.css); .a { background: url( \'/img/c.png\' ) } .b { background: url(data:image/svg+xml;base64,AAA=) } .c { background: url(img/d.png) } .d { background: url(//cdn.test/e.png) }';

        $this->assertSame(
            '@import "../css/a.css"; @import url(../css/b.css); .a { background: url(\'../img/c.png\') } .b { background: url(data:image/svg+xml;base64,AAA=) } .c { background: url(img/d.png) } .d { background: url(//cdn.test/e.png) }',
            $this->rewriter->rewriteCss($css, '../')
        );
    }

    public function testExtractsHtmlUrls(): void
    {
        $html = '<link rel="stylesheet" href="/a.css"><script src="/a.css"></script><img src="/b.png?v=1" srcset="/c.png 2x"><a href="/page">x</a><img src="https://cdn.test/d.png">';

        $this->assertSame(['/a.css', '/b.png?v=1', '/c.png'], $this->rewriter->extractHtmlUrls($html));
    }

    public function testExtractsCssUrls(): void
    {
        $css = '@import "/a.css"; @import "b.css"; .a { background: url(/c.png), url(../d.png?x#y); } .b { background: url(data:image/png;base64,AA==) url(https://cdn.test/e.png) url(#frag) url(//cdn.test/f.png) }';

        $this->assertSame(['/a.css', 'b.css', '/c.png', '../d.png?x#y'], $this->rewriter->extractCssUrls($css));
    }
}
