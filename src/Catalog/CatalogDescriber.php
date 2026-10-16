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

namespace IQ2i\StoriaBundle\Catalog;

use IQ2i\StoriaBundle\Config\ViewConfiguration;
use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\Test\VariantRunner;
use Symfony\Component\Config\Definition\Processor;

/**
 * Describes the catalog as plain arrays: the views, their variants and the resolved arguments of each variant.
 * It is what storia:list prints, for humans and for tools reading the catalog.
 *
 * @internal
 */
readonly class CatalogDescriber
{
    public function __construct(
        private string $defaultPath,
        private VariantRunner $variantRunner,
        private YamlPreProcessor $yamlPreProcessor,
        private DataResolver $dataResolver,
    ) {
    }

    /**
     * @return list<array{path: string, type: ?string, source: ?string, file: ?string, variants: list<array{name: string, args: array<string, mixed>, error: ?string}>, error: ?string}>
     */
    public function describe(?string $filter = null): array
    {
        $views = [];
        foreach ($this->variantRunner->findVariants($filter) as $path => $variants) {
            $views[] = $this->describeView($path, $variants);
        }

        return $views;
    }

    /**
     * @param list<string>|\Throwable $variants
     *
     * @return array{path: string, type: ?string, source: ?string, file: ?string, variants: list<array{name: string, args: array<string, mixed>, error: ?string}>, error: ?string}
     */
    private function describeView(string $path, array|\Throwable $variants): array
    {
        $file = $this->yamlPreProcessor->resolveFilePath($this->defaultPath, $path);
        $description = ['path' => $path, 'type' => null, 'source' => null, 'file' => null !== $file ? realpath($file) ?: $file : null, 'variants' => [], 'error' => null];

        if ($variants instanceof \Throwable) {
            $description['error'] = $variants->getMessage();

            return $description;
        }

        try {
            $config = (new Processor())->processConfiguration(new ViewConfiguration(), [$this->yamlPreProcessor->process($this->defaultPath, $path)]);
        } catch (\Throwable $throwable) {
            $description['error'] = $throwable->getMessage();

            return $description;
        }

        [$description['type'], $description['source']] = $this->typeAndSource($path, $config);

        foreach ($variants as $name) {
            $description['variants'][] = $this->describeVariant($name, $config);
        }

        return $description;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{string, ?string}
     */
    private function typeAndSource(string $path, array $config): array
    {
        return match (true) {
            isset($config['component']) => ['component', $config['component']],
            isset($config['form']) => ['form', $config['form']],
            isset($config['template']) => ['template', $config['template']],
            default => ['template', $this->defaultPath.'/'.$path.'.html.twig'],
        };
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{name: string, args: array<string, mixed>, error: ?string}
     */
    private function describeVariant(string $name, array $config): array
    {
        $variant = $config['variants'][$name] ?? [];
        $isForm = isset($config['form']);

        try {
            $args = $isForm
                ? $this->dataResolver->resolve(array_replace($config['options'] ?? [], $variant['options'] ?? []))
                : $this->dataResolver->resolve($variant['args'] ?? []);
        } catch (\Throwable $throwable) {
            return ['name' => $name, 'args' => [], 'error' => $throwable->getMessage()];
        }

        return ['name' => $name, 'args' => $this->normalize($args), 'error' => null];
    }

    /**
     * Makes resolved arguments printable: objects have no stable textual form, so they are summarized.
     */
    private function normalize(mixed $value): mixed
    {
        return match (true) {
            \is_array($value) => array_map($this->normalize(...), $value),
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,
            $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::ATOM),
            $value instanceof \JsonSerializable => $value->jsonSerialize(),
            $value instanceof \Stringable => (string) $value,
            \is_object($value) => 'object('.$value::class.')',
            default => $value,
        };
    }
}
