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

namespace IQ2i\StoriaBundle\Tests\Controls;

use IQ2i\StoriaBundle\Controls\ComponentIntrospector;
use IQ2i\StoriaBundle\Controls\PropsExtractor;
use IQ2i\StoriaBundle\Tests\Fixtures\Panel;
use IQ2i\StoriaBundle\Tests\Fixtures\Toast;
use Symfony\UX\TwigComponent\ComponentMetadata;

class ComponentIntrospectorTest extends TwigTestCase
{
    private ComponentIntrospector $introspector;

    protected function setUp(): void
    {
        $this->introspector = new ComponentIntrospector(new PropsExtractor($this->createTwig([
            'card.html.twig' => "{% props title = 'Card', count = 3 %}",
            'empty.html.twig' => '<p></p>',
        ])));
    }

    private function metadata(string $template, ?string $class = null, bool $exposePublicProps = true): ComponentMetadata
    {
        $config = ['key' => 'X', 'template' => $template];
        if (null !== $class) {
            $config += ['class' => $class, 'service_id' => 'x', 'expose_public_props' => $exposePublicProps];
        }

        return new ComponentMetadata($config);
    }

    public function testAnonymousComponentOnlyHasItsPropsTag(): void
    {
        $props = $this->introspector->props($this->metadata('card.html.twig'));

        $this->assertSame(['title', 'count'], array_keys($props));
        $this->assertSame('props', $props['title']->source);
        $this->assertSame([], $this->introspector->exposedNames($this->metadata('card.html.twig')));
    }

    public function testPublicPropertiesOfTheClass(): void
    {
        $props = $this->introspector->props($this->metadata('empty.html.twig', Panel::class));

        $this->assertSame(['title', 'rows', 'closable', 'size', 'items', 'date', 'required'], array_keys($props));
        $this->assertSame('class', $props['rows']->source);
        $this->assertSame(2, $props['rows']->default);
        $this->assertTrue($props['rows']->hasDefault);
        $type = $props['rows']->type;
        \assert($type instanceof \ReflectionNamedType);
        $this->assertSame('int', $type->getName());
        $this->assertFalse($props['required']->hasDefault);
        $this->assertSame('Title of the panel.', $props['title']->directive->description);
        $this->assertSame('textarea', $props['title']->directive->type);
        $this->assertNull($props['rows']->directive);
    }

    public function testPublicPropertiesAreIgnoredWhenNotExposed(): void
    {
        $this->assertSame([], $this->introspector->props($this->metadata('empty.html.twig', Panel::class, false)));
    }

    public function testMountParametersAreProps(): void
    {
        $props = $this->introspector->props($this->metadata('empty.html.twig', Toast::class, false));

        $this->assertSame(['message', 'delay', 'at'], array_keys($props));
        $this->assertSame('class', $props['message']->source);
        $this->assertFalse($props['message']->hasDefault);
        $this->assertSame('The text to display', $props['message']->directive->description);
        $this->assertSame(5, $props['delay']->default);
        $this->assertSame('range', $props['delay']->directive->type);
        $this->assertSame(['min' => 1.0, 'max' => 30.0], $props['delay']->directive->options);
        $this->assertNull($props['at']->directive);
        $this->assertTrue($props['at']->type?->allowsNull());
    }

    public function testMountParametersTakePrecedenceOverTheProperties(): void
    {
        $props = $this->introspector->props($this->metadata('empty.html.twig', Toast::class));

        $this->assertSame(['message', 'delay', 'at'], array_keys($props));
        $this->assertSame('The text to display', $props['message']->directive?->description);
    }

    public function testExposedNames(): void
    {
        $this->assertSame(['label', 'summary', 'isUrgent'], $this->introspector->exposedNames($this->metadata('empty.html.twig', Toast::class)));
    }
}
