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

use IQ2i\StoriaBundle\Tests\Controls\TwigTestCase;
use IQ2i\StoriaBundle\Tests\TestApplication\Factory\ProductFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IframeControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;

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

    public function testSharedMenuFeedsComponentAndPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/storia/iframe/components/header?variant=default');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('nav', 'Home');
        $this->assertSelectorTextContains('nav', 'Shop');

        $client->request('GET', '/storia/iframe/pages/home/home?variant=default');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('nav', 'Home');
        $this->assertSelectorTextContains('nav', 'Shop');
        $this->assertCount(4, $client->getCrawler()->filter('ul.products li'));
        $this->assertSelectorTextContains('ul.products', 'Product 4');
    }

    public function testBlockUsingLoremTag(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/button/button?variant=lorem');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('button', 'lorem ipsum dolor');
    }

    public function testFormOptionsUsingDataTag(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/form/choice?variant=default');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('select', 'In Stock');
        $this->assertSelectorTextContains('select', 'Out of Stock');
    }

    public function testCustomFormVariantOverridesTheOptions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/form/choice?variant=preselected');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('option[selected]', 'In Stock');
    }

    public function testCustomFormVariantsKeepTheAutomaticOnes(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/storia/components/form/choice?variant=preselected');

        $this->assertResponseIsSuccessful();
        $labels = $crawler->filter('header > .tabs > .tabs__item')->each(static fn ($node) => trim($node->text()));
        $this->assertSame(['Default', 'Disabled', 'Error', 'Preselected'], $labels);
    }

    public function testUnknownView(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/unknown?variant=default');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function requestWithOverrides(string $view, array $overrides, string $variant = 'default'): \Symfony\Component\DomCrawler\Crawler
    {
        $this->client ??= static::createClient();

        return $this->client->request('GET', '/storia/iframe/components/'.$view.'?variant='.$variant.'&overrides='.rawurlencode((string) json_encode($overrides)));
    }

    public function testOverridesApplyToAnAnonymousComponent(): void
    {
        TwigTestCase::requirePropDocumentation();

        $crawler = $this->requestWithOverrides('card', ['title' => 'Hello', 'count' => 7, 'highlighted' => true, 'accent' => '#FF0000', 'align' => 'center']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Hello', $crawler->filter('h2')->text());
        $this->assertSame('7', $crawler->filter('.count')->text());
        $this->assertSelectorExists('article.card--highlighted[data-align="center"]');
        $this->assertSame('--accent: #ff0000', $crawler->filter('article')->attr('style'));
    }

    public function testOverridesOutsideTheDirectiveAreIgnored(): void
    {
        TwigTestCase::requirePropDocumentation();

        $crawler = $this->requestWithOverrides('card', ['count' => 12, 'accent' => 'red', 'align' => 'top']);

        $this->assertSame('3', $crawler->filter('.count')->text());
        $this->assertSelectorExists('article[data-align="left"]');
        $this->assertSame('--accent: #0f172a', $crawler->filter('article')->attr('style'));
    }

    public function testOverridesApplyToTheMountParameters(): void
    {
        $crawler = $this->requestWithOverrides('snackbar', ['message' => 'done', 'delay' => 12]);

        $this->assertSame('DONE', $crawler->filter('.snackbar')->text());
        $this->assertSame('12', $crawler->filter('.snackbar')->attr('data-delay'));
    }

    public function testOverridesApplyToTheTypesOfAPage(): void
    {
        TwigTestCase::requirePropDocumentation();

        $this->client ??= static::createClient();
        $crawler = $this->client->request('GET', '/storia/iframe/pages/documented/documented?variant=default&overrides='.rawurlencode((string) json_encode(['message' => "Two\nlines", 'columns' => 3])));

        $this->assertResponseIsSuccessful();
        $this->assertSame("Two\nlines", $crawler->filter('p[data-columns="3"]')->text(normalizeWhitespace: false));
    }

    public function testWithoutOverridesTheDefaultsApply(): void
    {
        $crawler = $this->requestWithOverrides('card', []);

        $this->assertSame('Card title', $crawler->filter('h2')->text());
        $this->assertSelectorNotExists('article.card--highlighted');
    }

    public function testOverridesApplyToAClassComponent(): void
    {
        $crawler = $this->requestWithOverrides('notice', ['level' => 'danger', 'dismissible' => true, 'kind' => 'warning']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.notice.notice-danger.notice-warning[data-dismissible]');
        $this->assertSame('Something happened', trim($crawler->filter('.notice')->text()));
    }

    public function testInvalidEnumOverrideIsIgnored(): void
    {
        $this->requestWithOverrides('notice', ['kind' => 'huge']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.notice-info');
    }

    public function testOverridesApplyToATemplate(): void
    {
        $this->requestWithOverrides('badge', ['class' => 'custom-class']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.custom-class');
    }

    public function testOverridesAreNeverResolvedAsPhpCallables(): void
    {
        $this->requestWithOverrides('product', ['product' => ProductFactory::class.'::createExpensive']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('body', 'Premium Product');
    }

    public function testStringOverridesAreNeverResolvedAsData(): void
    {
        $crawler = $this->requestWithOverrides('card', ['title' => '!data product.expensive']);

        $this->assertSame('!data product.expensive', $crawler->filter('h2')->text());

        $crawler = $this->requestWithOverrides('card', ['title' => ProductFactory::class.'::createDefault']);

        $this->assertStringContainsString('ProductFactory::createDefault', $crawler->filter('h2')->text());
    }

    public function testOverridesAreNotInterpretedAsTwig(): void
    {
        $crawler = $this->requestWithOverrides('card', ['title' => '{{ 7*7 }}{% set x = 1 %}']);
        $this->assertSame('{{ 7*7 }}{% set x = 1 %}', $crawler->filter('h2')->text());

        $crawler = $this->requestWithOverrides('card', ['title' => '"/><script>alert(1)</script>{{ 7*7 }}']);
        $this->assertResponseIsSuccessful();
        $this->assertSame('"/><script>alert(1)</script>{{ 7*7 }}', $crawler->filter('h2')->text());
        $this->assertSelectorNotExists('body script');
    }

    public function testOverridesAreNotInterpretedAsTwigInATemplate(): void
    {
        $crawler = $this->requestWithOverrides('badge', ['class' => '{{ 7*7 }}']);

        $this->assertSame('{{ 7*7 }}', $crawler->filter('span')->attr('class'));
    }

    public function testUnknownAndUnsafeKeysAreIgnored(): void
    {
        $crawler = $this->requestWithOverrides('card', ['title" onclick="x' => 'bad', "x'] }}{{ 7*7 }}{{ y['" => 'bad', 'unknown' => 'bad', 'title' => 'Good']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Good', $crawler->filter('h2')->text());
        $this->assertSelectorNotExists('[onclick]');
        $this->assertSelectorNotExists('.card[unknown]');
    }

    public function testReadOnlyControlsCannotBeOverridden(): void
    {
        $this->requestWithOverrides('teaser', ['shouted' => 'lower']);

        $this->assertSelectorExists('.teaser[data-shouted="X"]');
    }

    public function testBlocksCannotBeOverridden(): void
    {
        $this->requestWithOverrides('notice', ['content' => 'Injected', 'blocks' => ['content' => 'Injected']]);

        $this->assertSelectorTextNotContains('body', 'Injected');
    }

    public function testMalformedOverridesAreIgnored(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/card?variant=default&overrides=%7Bnot-json');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Card title');

        $client->request('GET', '/storia/iframe/components/card?variant=default&overrides[]=x');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Card title');
    }

    public function testArrayArgWithSpecialCharactersIsRenderedAsIs(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/product?variant=special_characters');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('h3', 'It\'s a café "quoted" {{ 7*7 }}');
        $this->assertSelectorTextContains('.price', '$5');
    }

    public function testProviderObjectAndUntrustedTextAreNotInterpretedAsTwig(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/notice?variant=provider_object');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.notice-danger');
        $this->assertSelectorTextSame('.notice', 'Hello {{ 7*7 }} {% if true %}yes{% endif %}');
    }

    public function testBlocksAreRenderedAsHtml(): void
    {
        $client = static::createClient();
        $client->request('GET', '/storia/iframe/components/footer?variant=legal_links');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('a[href="/legal"]', 'Legal notice');
    }
}
