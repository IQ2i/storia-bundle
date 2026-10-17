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

namespace IQ2i\StoriaBundle\Data;

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Data\Exception\DataNotFoundException;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Reads named data files from "<default_path>/data/".
 *
 * @internal
 */
class DataRepository
{
    private const string DIRECTORY = 'data';

    /** @var array<string, array<mixed>> */
    private array $cache = [];

    public function __construct(
        private readonly string $defaultPath,
        private readonly YamlPreProcessor $yamlPreProcessor,
    ) {
    }

    /**
     * Returns the raw (tags unresolved) value designated by a reference such as "menu",
     * "shop/product.expensive" or "menu.items.0".
     *
     * @param \Closure(TaggedValue): mixed $resolveTag Resolves a tag found on the way down to the requested key
     */
    public function get(string $ref, \Closure $resolveTag): mixed
    {
        $segments = explode('.', $ref);

        for ($length = \count($segments); $length >= 1; --$length) {
            $name = implode('.', \array_slice($segments, 0, $length));
            $data = $this->load($name);
            if (null === $data) {
                continue;
            }

            return $this->walk($data, \array_slice($segments, $length), $name, $resolveTag);
        }

        throw new DataNotFoundException(\sprintf('Data "%s" not found: expected file %s/%s/%s.yaml', $ref, $this->defaultPath, self::DIRECTORY, $segments[0]));
    }

    /**
     * @return array<mixed>|null
     */
    private function load(string $name): ?array
    {
        if (\array_key_exists($name, $this->cache)) {
            return $this->cache[$name];
        }

        $basePath = $this->defaultPath.'/'.self::DIRECTORY;
        if (null === $this->yamlPreProcessor->resolveFilePath($basePath, $name)) {
            return null;
        }

        $data = $this->yamlPreProcessor->process($basePath, $name);
        if ([] === $data) {
            throw new DataNotFoundException(\sprintf('Data "%s" is empty: check file %s/%s/%s.yaml', $name, $this->defaultPath, self::DIRECTORY, $name));
        }

        return $this->cache[$name] = $data;
    }

    /**
     * @param array<mixed> $data
     * @param list<string> $path
     */
    private function walk(mixed $data, array $path, string $name, \Closure $resolveTag): mixed
    {
        $walked = [];
        foreach ($path as $key) {
            if ($data instanceof TaggedValue) {
                $data = $resolveTag($data);
            }

            $walked[] = $key;
            if (!\is_array($data) || !\array_key_exists($key, $data)) {
                throw new DataNotFoundException(\sprintf('Key "%s" not found in data "%s".', implode('.', $walked), $name));
            }

            $data = $data[$key];
        }

        return $data;
    }
}
