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

namespace IQ2i\StoriaBundle\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Pre-processes a Storia YAML file to resolve `include:` and `extends:` directives
 * before the config is validated by ViewConfiguration.
 *
 * - `include:` borrows YAML anchor definitions from one or more shared files.
 *   Only the `_definitions:` block of each included file is injected before the
 *   main file's content so that YAML anchors are available cross-file.
 * - `extends:` performs a deep merge of the parent config, allowing child files to
 *   override only the parts they need.
 * - `_definitions:` sections are stripped from the final result.
 */
readonly class YamlPreProcessor
{
    private const string DEFINITIONS_KEY = '_definitions';

    private const string INCLUDE_KEY = 'include';

    private const string EXTENDS_KEY = 'extends';

    /** Prefix used to rename included _definitions: blocks to avoid duplicate-key errors. */
    private const string DEFS_PREFIX = '_storia_defs_';

    /**
     * Returns the absolute path of a Storia YAML file, trying `.yaml` then `.yml`.
     * Returns null if neither extension exists.
     */
    public function resolveFilePath(string $basePath, string $path): ?string
    {
        foreach (['yaml', 'yml'] as $extension) {
            $filePath = $basePath.'/'.$path.'.'.$extension;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        return null;
    }

    /**
     * Processes a Storia YAML file and returns the resolved config array.
     *
     * @return array<string, mixed>
     */
    public function process(string $basePath, string $path): array
    {
        return $this->processFile($basePath, $path, []);
    }

    /**
     * Processes a single YAML file with circular-extends detection.
     *
     * @param list<string> $resolvedPaths Stack of absolute paths being processed (for cycle detection)
     *
     * @return array<string, mixed>
     */
    private function processFile(string $basePath, string $path, array $resolvedPaths): array
    {
        $filePath = $this->resolveFilePath($basePath, $path)
            ?? throw new \RuntimeException(\sprintf('Storia YAML file "%s" not found (tried .yaml and .yml).', $basePath.'/'.$path));

        if (\in_array($filePath, $resolvedPaths, true)) {
            throw new \RuntimeException(\sprintf('Circular reference detected for Storia YAML file "%s".', $filePath));
        }

        $resolvedPaths[] = $filePath;
        $rawContent = (string) file_get_contents($filePath);

        // Detect include directives from raw content (before full parse, as the file may use
        // anchors defined in included files which would cause a parse error if done alone).
        $includeFiles = $this->extractIncludeDirectives($rawContent);

        // Build the YAML string to parse.
        // For each included file we inject ONLY its `_definitions:` block, renamed with a unique
        // prefix to avoid duplicate-key errors when multiple files are included.
        // The main file's full content comes last so its anchor references resolve correctly.
        $yamlParts = [];
        foreach ($includeFiles as $index => $includePath) {
            $includeAbsPath = $this->resolveFilePath($basePath, $includePath);

            if (null === $includeAbsPath) {
                throw new \RuntimeException(\sprintf('Included Storia YAML file "%s" does not exist (tried .yaml and .yml).', $basePath.'/'.$includePath));
            }

            $includedContent = (string) file_get_contents($includeAbsPath);
            $defsBlock = $this->extractDefinitionsBlock($includedContent, $index);
            if ('' !== $defsBlock) {
                $yamlParts[] = $defsBlock;
            }
        }

        $yamlParts[] = $rawContent;

        /** @var array<string, mixed> $data */
        $data = Yaml::parse(implode("\n", $yamlParts)) ?? [];

        // Save extends path before stripping meta keys.
        $extendsPath = isset($data[self::EXTENDS_KEY]) ? (string) $data[self::EXTENDS_KEY] : null;

        // Strip all meta keys that must not reach ViewConfiguration.
        $this->stripMetaKeys($data);

        // Handle extends: deep-merge the parent config first, then apply this file on top.
        if (null !== $extendsPath) {
            $parentData = $this->processFile($basePath, $extendsPath, $resolvedPaths);
            $data = $this->deepMerge($parentData, $data);
        }

        return $data;
    }

    /**
     * Extracts paths listed under the `include:` key from raw YAML content.
     *
     * Supported syntaxes:
     *   include: path/to/file
     *   include: [path/to/file1, path/to/file2]
     *   include:
     *     - path/to/file1
     *     - path/to/file2
     *
     * @return list<string>
     */
    private function extractIncludeDirectives(string $rawContent): array
    {
        $lines = explode("\n", $rawContent);
        $includeLines = [];
        $inInclude = false;
        $hasInlineValue = false;

        foreach ($lines as $line) {
            if (preg_match('/^include:\s*(.*)$/', $line, $matches)) {
                $inInclude = true;
                $includeLines[] = $line;
                $hasInlineValue = '' !== trim($matches[1]);
                continue;
            }

            if ($inInclude) {
                if ($hasInlineValue) {
                    // Inline value was already on the include: line; stop.
                    break;
                }

                if (preg_match('/^[ \t]/', $line)) {
                    // Indented continuation of the include block.
                    $includeLines[] = $line;
                    continue;
                }

                // Non-indented line: end of include block.
                break;
            }
        }

        if ([] === $includeLines) {
            return [];
        }

        /** @var array<string, mixed> $parsed */
        $parsed = Yaml::parse(implode("\n", $includeLines)) ?? [];
        $value = $parsed[self::INCLUDE_KEY] ?? null;

        if (null === $value) {
            return [];
        }

        /* @var list<string> */
        return \is_array($value) ? array_values($value) : [$value];
    }

    /**
     * Extracts the `_definitions:` block from a raw YAML string and renames it
     * to a unique key to avoid duplicate-key errors during concatenation.
     *
     * Only the lines belonging to the `_definitions:` mapping are extracted.
     * All YAML anchors defined inside that block remain intact.
     */
    private function extractDefinitionsBlock(string $rawContent, int $index): string
    {
        $lines = explode("\n", $rawContent);
        $result = [];
        $inDefinitions = false;

        foreach ($lines as $line) {
            if (preg_match('/^_definitions:\s*$/', $line)) {
                // Rename to a unique key to avoid duplicate-key errors.
                $result[] = self::DEFS_PREFIX.$index.':';
                $inDefinitions = true;
                continue;
            }

            if ($inDefinitions) {
                if (preg_match('/^[ \t]/', $line) || '' === trim($line)) {
                    $result[] = $line;
                } else {
                    // Non-indented line: end of _definitions block.
                    $inDefinitions = false;
                }
            }
        }

        return implode("\n", $result);
    }

    /**
     * Removes all meta keys (`_definitions:`, `_storia_defs_*`, `include:`, `extends:`)
     * from the parsed data array in place.
     *
     * @param array<string, mixed> $data
     */
    private function stripMetaKeys(array &$data): void
    {
        foreach (array_keys($data) as $key) {
            if (
                self::DEFINITIONS_KEY === $key
                || self::INCLUDE_KEY === $key
                || self::EXTENDS_KEY === $key
                || str_starts_with((string) $key, self::DEFS_PREFIX)
            ) {
                unset($data[$key]);
            }
        }
    }

    /**
     * Recursively merges $override into $base, with $override values taking precedence.
     * Associative arrays are merged deeply; sequential arrays are replaced entirely.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (\is_array($value) && isset($base[$key]) && \is_array($base[$key]) && $this->isAssoc($base[$key])) {
                $base[$key] = $this->deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Returns true if the array is associative (string keys), false if sequential.
     *
     * @param array<mixed> $array
     */
    private function isAssoc(array $array): bool
    {
        return [] !== $array && array_keys($array) !== range(0, \count($array) - 1);
    }
}
