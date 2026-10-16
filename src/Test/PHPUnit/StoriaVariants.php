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

namespace IQ2i\StoriaBundle\Test\PHPUnit;

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\View\ViewFinder;

/**
 * Lists the variants of a storia directory for a PHPUnit data provider. It reads the YAML files
 * without a container, as a data provider runs before any kernel can be booted.
 */
final class StoriaVariants
{
    private const array FORM_VARIANTS = ['default', 'disabled', 'error'];

    /**
     * Data provider reading the directory from the STORIA_PATH environment variable, or ./storia.
     *
     * @return array<string, array{string, string}>
     */
    public static function provide(): array
    {
        $path = getenv('STORIA_PATH');

        return self::fromDirectory(\is_string($path) && '' !== $path ? $path : getcwd().'/storia');
    }

    /**
     * @return array<string, array{string, string}> data sets named "view:variant", holding the view path and the variant name
     */
    public static function fromDirectory(string $storiaPath, ?string $filter = null): array
    {
        $preProcessor = new YamlPreProcessor();

        $dataSets = [];
        foreach ((new ViewFinder($storiaPath))->findPaths() as $path) {
            $config = $preProcessor->process($storiaPath, $path);
            $variants = \array_key_exists('form', $config) ? self::FORM_VARIANTS : array_map(self::normalizeName(...), array_keys($config['variants'] ?? []));

            foreach ($variants as $variant) {
                $name = $path.':'.$variant;
                if (null === $filter || '' === $filter || str_contains($name, $filter)) {
                    $dataSets[$name] = [$path, $variant];
                }
            }
        }

        return $dataSets;
    }

    /**
     * Symfony Config turns the hyphens of the keys into underscores, which is the name the builders expose.
     */
    private static function normalizeName(int|string $name): string
    {
        return str_replace('-', '_', (string) $name);
    }
}
