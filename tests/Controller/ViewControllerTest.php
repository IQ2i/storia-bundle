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
        $this->assertSelectorExists('script[src="/bundles/iq2istoria/storia.js"]');
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

    public function testAnonymousComponentGetsOneControlPerPropWithoutAnyYaml(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/card?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Controls', trim($crawler->filter('main .tabs__item_link')->first()->text()));
        $this->assertSelectorExists('main .tabs__item_link.tab-is-active[href="#controls"]');
        $this->assertSelectorExists('#controls[data-tab-target="tabPanel"]:not(.hidden)');
        $this->assertSelectorExists('#twig.hidden');

        $fields = $crawler->filter('#controls [data-variant-controls-target="field"]');
        $this->assertCount(3, $fields);
        $this->assertSame(['text', 'number', 'checkbox'], $fields->each(static fn ($field): string => $field->attr('data-type')));
        $this->assertSame('Card title', $crawler->filter('#control-title')->attr('value'));
        $this->assertSame('"Card title"', $crawler->filter('#control-title')->attr('data-initial-value'));
        $this->assertSame(['props'], array_unique($crawler->filter('.controls__badges .controls__badge:last-child')->each(static fn ($badge): string => $badge->text())));
        $this->assertSelectorExists('[data-variant-controls-controls-outlet=".main__content"]');
        $this->assertSelectorTextContains('[data-variant-controls-target="copyLabel"]', 'Copy variant YAML');
    }

    public function testControlsOfAClassComponentAndATemplate(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/notice?variant=warning');

        $this->assertSame(['info', 'warning', 'danger'], $crawler->filter('#control-kind option')->each(static fn ($option): string => $option->attr('value')));
        $this->assertSame('info', $crawler->filter('#control-kind option[selected]')->attr('value'));
        $this->assertSame('warning', $crawler->filter('#control-level')->attr('value'));
        $this->assertSelectorExists('#control-dismissible[type="checkbox"]:not([checked])');

        $crawler = $client->request('GET', '/storia/components/badge?variant=default');

        $this->assertCount(1, $crawler->filter('#controls [data-variant-controls-target="field"]'));
        $this->assertSelectorTextContains('#controls .controls__badge--yaml', 'yaml');
    }

    public function testControlsAreReadOnlyWhenTheyCannotBeEdited(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/teaser?variant=default');

        $this->assertSelectorExists('#control-shouted[disabled]');
        $this->assertSelectorNotExists('#control-shouted[data-initial-value]');
        $this->assertSelectorExists('#control-items:not([disabled])');
        $this->assertSelectorTextContains('.controls__badge--note', 'expression');
        $this->assertStringContainsString('"a"', $crawler->filter('#control-items')->text());
    }

    public function testViewsWithoutControlsHaveNoControlsTab(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/form/text?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#controls');
        $this->assertSelectorNotExists('a[href="#controls"]');
        $this->assertSelectorExists('main .tabs__item_link.tab-is-active[href="#twig"]');
        $this->assertSelectorExists('#twig:not(.hidden)');
    }

    public function testOverridesAreRestoredInTheShell(): void
    {
        $client = static::createClient();
        $overrides = (string) json_encode(['title' => 'Hello "world"', 'count' => 5, 'highlighted' => true]);
        $crawler = $client->request('GET', '/storia/components/card?variant=default&overrides='.rawurlencode($overrides));

        $this->assertSame('Hello "world"', $crawler->filter('#control-title')->attr('value'));
        $this->assertSame('"Card title"', $crawler->filter('#control-title')->attr('data-initial-value'));
        $this->assertSame('5', $crawler->filter('#control-count')->attr('value'));
        $this->assertSelectorExists('#control-highlighted[checked]');

        $expected = '/storia/iframe/components/card?variant=default&overrides='.rawurlencode($overrides);
        $this->assertSame($expected, $crawler->filter('iframe')->attr('src'));
        $this->assertSame($expected, $crawler->filter('a[data-controls-target="iframeLink"]')->attr('href'));
        $this->assertSame('/storia/iframe/components/card?variant=default', $crawler->filter('[data-variant-controls-iframe-url-value]')->attr('data-variant-controls-iframe-url-value'));
    }

    public function testOverridesAppearInTheIncludeTab(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/components/card?variant=default&overrides='.rawurlencode((string) json_encode(['title' => 'A "b"', 'count' => 5, 'highlighted' => true])));

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('title=&quot;A &amp;quot;b&amp;quot;&quot;', $content);
        $this->assertStringContainsString(':count=&quot;5&quot;', $content);
        $this->assertStringContainsString(':highlighted=&quot;true&quot;', $content);
    }
}
