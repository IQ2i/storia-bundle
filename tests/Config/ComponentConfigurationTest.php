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

namespace IQ2i\StoriaBundle\Tests\Config;

use IQ2i\StoriaBundle\Config\ViewConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ComponentConfigurationTest extends TestCase
{
    public function testWithValidConfig(): void
    {
        $yaml = Yaml::parse(<<<EOF
            template: some_template.html.twig
            variants:
                default:
                    args:
                        foo: bar
            EOF);
        $config = (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);

        $this->assertEquals([
            'template' => 'some_template.html.twig',
            'variants' => [
                'default' => [
                    'args' => ['foo' => 'bar'],
                    'blocks' => [],
                ],
            ],
            'options' => [],
        ], $config);
    }

    public function testWithKnownTags(): void
    {
        $yaml = Yaml::parse(<<<EOF
            template: some_template.html.twig
            variants:
                default:
                    args:
                        menu: !data menu
                        items: !repeat { count: 2, item: !lorem 3 }
            EOF, Yaml::PARSE_CUSTOM_TAGS);
        $config = (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);

        $this->assertSame('data', $config['variants']['default']['args']['menu']->getTag());
    }

    public function testWithUnknownTag(): void
    {
        $yaml = Yaml::parse(<<<EOF
            template: some_template.html.twig
            variants:
                default:
                    args:
                        foo: [!foo bar]
            EOF, Yaml::PARSE_CUSTOM_TAGS);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown tag "!foo"');
        (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);
    }

    public function testWithTemplateAndComponentTogether(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"template", "component" and "form" cannot be used together.');

        $yaml = Yaml::parse(<<<EOF
            template: some_template.html.twig
            component: some_component.html.twig
            EOF);
        (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);
    }

    public function testFormVariantsAcceptOptionsAndWithErrors(): void
    {
        $yaml = Yaml::parse(<<<EOF
            form: Symfony\Component\Form\Extension\Core\Type\ChoiceType
            variants:
                preselected:
                    options:
                        data: b
                    with_errors: true
            EOF);
        $config = (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);

        $this->assertSame(['data' => 'b'], $config['variants']['preselected']['options']);
        $this->assertTrue($config['variants']['preselected']['with_errors']);
    }

    public function testFormVariantsRejectArgsAndBlocks(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('"args" and "blocks" are not allowed in the variants of a form view');

        $yaml = Yaml::parse(<<<EOF
            form: Symfony\Component\Form\Extension\Core\Type\ChoiceType
            variants:
                custom:
                    args:
                        foo: bar
            EOF);
        (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);
    }

    public function testTemplateVariantsRejectFormOptions(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('allowed in the variants of a form view only');

        $yaml = Yaml::parse(<<<EOF
            template: some_template.html.twig
            variants:
                default:
                    with_errors: false
            EOF);
        (new Processor())->processConfiguration(new ViewConfiguration(), [$yaml]);
    }
}
