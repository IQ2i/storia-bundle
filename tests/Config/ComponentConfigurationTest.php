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
}
