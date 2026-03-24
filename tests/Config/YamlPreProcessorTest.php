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

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use PHPUnit\Framework\TestCase;

class YamlPreProcessorTest extends TestCase
{
    private string $tmpDir;

    private YamlPreProcessor $processor;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/storia_test_'.uniqid();
        mkdir($this->tmpDir, 0o777, true);
        $this->processor = new YamlPreProcessor();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    // -------------------------------------------------------------------------
    // Basic processing (no include / extends)
    // -------------------------------------------------------------------------

    public function testProcessSimpleYaml(): void
    {
        $this->writeYaml('badge', <<<YAML
            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        class: bg-blue-100
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertSame('ui/badge.html.twig', $result['template']);
        $this->assertSame('bg-blue-100', $result['variants']['default']['args']['class']);
    }

    public function testDefinitionsKeyIsStripped(): void
    {
        $this->writeYaml('badge', <<<YAML
            _definitions:
                my_class: &my_class
                    class: bg-blue-100

            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        <<: *my_class
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertArrayNotHasKey('_definitions', $result);
        $this->assertSame('bg-blue-100', $result['variants']['default']['args']['class']);
    }

    // -------------------------------------------------------------------------
    // include:
    // -------------------------------------------------------------------------

    public function testIncludeScalar(): void
    {
        $this->writeYaml('_shared', <<<YAML
            _definitions:
                badge_class: &badge_class
                    class: bg-blue-100
            YAML);

        $this->writeYaml('badge', <<<YAML
            include: _shared

            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        <<: *badge_class
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertArrayNotHasKey('include', $result);
        $this->assertArrayNotHasKey('_definitions', $result);
        $this->assertSame('bg-blue-100', $result['variants']['default']['args']['class']);
    }

    public function testIncludeInlineArray(): void
    {
        $this->writeYaml('_colors', <<<YAML
            _definitions:
                blue: &blue
                    class: bg-blue-100
            YAML);

        $this->writeYaml('_sizes', <<<YAML
            _definitions:
                small: &small
                    size: sm
            YAML);

        $this->writeYaml('badge', <<<YAML
            include: [_colors, _sizes]

            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        <<: *blue
                        <<: *small
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertArrayNotHasKey('include', $result);
        $this->assertSame('ui/badge.html.twig', $result['template']);
    }

    public function testIncludeBlockSequence(): void
    {
        $this->writeYaml('_colors', <<<YAML
            _definitions:
                blue: &blue
                    class: bg-blue-100
            YAML);

        $this->writeYaml('_sizes', <<<YAML
            _definitions:
                small: &small
                    size: sm
            YAML);

        $this->writeYaml('badge', <<<YAML
            include:
                - _colors
                - _sizes

            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        <<: *blue
                        <<: *small
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertArrayNotHasKey('include', $result);
        $this->assertSame('ui/badge.html.twig', $result['template']);
    }

    public function testIncludeMissingFileThrowsException(): void
    {
        $this->writeYaml('badge', <<<YAML
            include: _nonexistent

            template: ui/badge.html.twig
            YAML);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not exist (tried .yaml and .yml)');

        $this->processor->process($this->tmpDir, 'badge');
    }

    public function testProcessYmlExtension(): void
    {
        file_put_contents($this->tmpDir.'/badge.yml', <<<YAML
            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        class: bg-blue-100
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertSame('ui/badge.html.twig', $result['template']);
    }

    public function testIncludeYmlExtension(): void
    {
        file_put_contents($this->tmpDir.'/_shared.yml', <<<YAML
            _definitions:
                badge_class: &badge_class
                    class: bg-blue-100
            YAML);

        $this->writeYaml('badge', <<<YAML
            include: _shared

            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        <<: *badge_class
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertSame('bg-blue-100', $result['variants']['default']['args']['class']);
    }

    public function testResolveFilePathPrefersYamlOverYml(): void
    {
        $this->writeYaml('badge', 'template: yaml_version.html.twig');
        file_put_contents($this->tmpDir.'/badge.yml', 'template: yml_version.html.twig');

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertSame('yaml_version.html.twig', $result['template']);
    }

    public function testIncludeKeyIsStrippedFromResult(): void
    {
        $this->writeYaml('_shared', <<<YAML
            _definitions:
                x: &x
                    foo: bar
            YAML);

        $this->writeYaml('badge', <<<YAML
            include: _shared

            template: ui/badge.html.twig
            YAML);

        $result = $this->processor->process($this->tmpDir, 'badge');

        $this->assertArrayNotHasKey('include', $result);
    }

    // -------------------------------------------------------------------------
    // extends:
    // -------------------------------------------------------------------------

    public function testExtendsInheritsParentConfig(): void
    {
        $this->writeYaml('base', <<<YAML
            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        class: bg-blue-100
                large:
                    args:
                        class: bg-blue-100 text-sm
            YAML);

        $this->writeYaml('child', <<<YAML
            extends: base
            YAML);

        $result = $this->processor->process($this->tmpDir, 'child');

        $this->assertSame('ui/badge.html.twig', $result['template']);
        $this->assertArrayHasKey('default', $result['variants']);
        $this->assertArrayHasKey('large', $result['variants']);
    }

    public function testExtendsOverridesSpecificVariant(): void
    {
        $this->writeYaml('base', <<<YAML
            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        class: bg-blue-100
                large:
                    args:
                        class: bg-blue-100 text-sm
            YAML);

        $this->writeYaml('child', <<<YAML
            extends: base

            variants:
                default:
                    args:
                        class: bg-red-100
            YAML);

        $result = $this->processor->process($this->tmpDir, 'child');

        // Overridden variant
        $this->assertSame('bg-red-100', $result['variants']['default']['args']['class']);
        // Inherited variant
        $this->assertSame('bg-blue-100 text-sm', $result['variants']['large']['args']['class']);
    }

    public function testExtendsAddsNewVariant(): void
    {
        $this->writeYaml('base', <<<YAML
            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        class: bg-blue-100
            YAML);

        $this->writeYaml('child', <<<YAML
            extends: base

            variants:
                new_variant:
                    args:
                        class: bg-green-100
            YAML);

        $result = $this->processor->process($this->tmpDir, 'child');

        $this->assertArrayHasKey('default', $result['variants']);
        $this->assertArrayHasKey('new_variant', $result['variants']);
    }

    public function testExtendsKeyIsStrippedFromResult(): void
    {
        $this->writeYaml('base', <<<YAML
            template: ui/badge.html.twig
            YAML);

        $this->writeYaml('child', <<<YAML
            extends: base
            YAML);

        $result = $this->processor->process($this->tmpDir, 'child');

        $this->assertArrayNotHasKey('extends', $result);
    }

    public function testExtendsChain(): void
    {
        $this->writeYaml('grandparent', <<<YAML
            template: ui/badge.html.twig
            variants:
                default:
                    args:
                        class: bg-blue-100
            YAML);

        $this->writeYaml('parent', <<<YAML
            extends: grandparent

            variants:
                large:
                    args:
                        class: bg-blue-100 text-sm
            YAML);

        $this->writeYaml('child', <<<YAML
            extends: parent

            variants:
                default:
                    args:
                        class: bg-red-100
            YAML);

        $result = $this->processor->process($this->tmpDir, 'child');

        $this->assertSame('bg-red-100', $result['variants']['default']['args']['class']);
        $this->assertSame('bg-blue-100 text-sm', $result['variants']['large']['args']['class']);
    }

    // -------------------------------------------------------------------------
    // Circular reference detection
    // -------------------------------------------------------------------------

    public function testCircularExtendsThrowsException(): void
    {
        $this->writeYaml('a', <<<YAML
            extends: b
            template: ui/badge.html.twig
            YAML);

        $this->writeYaml('b', <<<YAML
            extends: a
            template: ui/badge.html.twig
            YAML);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular reference detected');

        $this->processor->process($this->tmpDir, 'a');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function writeYaml(string $path, string $content): void
    {
        $dir = \dirname($this->tmpDir.'/'.$path.'.yaml');
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        file_put_contents($this->tmpDir.'/'.$path.'.yaml', $content);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
