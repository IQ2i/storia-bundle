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
use IQ2i\StoriaBundle\Data\Exception\CircularDataReferenceException;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use Symfony\Component\Yaml\Tag\TaggedValue;

/**
 * Resolves the "!data", "!repeat" and "!lorem" tags, and legacy bare "Class::method" strings.
 */
readonly class DataResolver
{
    private const string METHOD_PATTERN = '/^[a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*::\w+$/';

    public function __construct(
        private DataRepository $repository,
        private ArgResolver $argResolver,
    ) {
    }

    public function resolve(mixed $value): mixed
    {
        return $this->doResolve($value, []);
    }

    /**
     * @param list<string> $stack References being resolved, to detect cycles
     */
    private function doResolve(mixed $value, array $stack): mixed
    {
        if ($value instanceof TaggedValue) {
            return match ($value->getTag()) {
                'data' => $this->resolveData($value->getValue(), $stack),
                'repeat' => $this->resolveRepeat($value->getValue(), $stack),
                'lorem' => LoremIpsum::fromSpec((string) $value->getValue()),
                default => throw new \InvalidArgumentException(\sprintf('Unknown tag "!%s": supported tags are !data, !repeat, !lorem.', $value->getTag())),
            };
        }

        if (\is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->doResolve($item, $stack), $value);
        }

        if (\is_string($value) && preg_match(self::METHOD_PATTERN, $value)) {
            return $this->argResolver->resolveReference($value);
        }

        return $value;
    }

    /**
     * @param list<string> $stack
     */
    private function resolveData(mixed $spec, array $stack): mixed
    {
        $with = [];
        if (\is_array($spec)) {
            $ref = $spec['ref'] ?? null;
            $with = $spec['with'] ?? [];
            if (!\is_string($ref) || !\is_array($with)) {
                throw new \InvalidArgumentException('Invalid !data: expected a reference or a mapping with a "ref" string and an optional "with" mapping.');
            }
        } elseif (\is_string($spec)) {
            $ref = $spec;
        } else {
            throw new \InvalidArgumentException('Invalid !data: expected a reference or a mapping with "ref".');
        }

        $ref = trim($ref);
        $resolved = $this->resolveRef($ref, $stack);

        if ([] !== $with) {
            if (!\is_array($resolved)) {
                throw new \InvalidArgumentException(\sprintf('Invalid !data: "with" can only override a mapping, but "%s" is not one.', $ref));
            }

            $resolved = YamlPreProcessor::deepMerge($resolved, $this->doResolve($with, $stack));
        }

        return $resolved;
    }

    /**
     * @param list<string> $stack
     */
    private function resolveRef(string $ref, array $stack): mixed
    {
        if (str_contains($ref, '::')) {
            return $this->argResolver->resolveReference($ref);
        }

        if (\in_array($ref, $stack, true)) {
            throw new CircularDataReferenceException(\sprintf('Circular data reference: %s.', implode(' -> ', [...$stack, $ref])));
        }

        $stack[] = $ref;
        $raw = $this->repository->get($ref, fn (TaggedValue $tag): mixed => $this->doResolve($tag, $stack));

        return $this->doResolve($raw, $stack);
    }

    /**
     * @param list<string> $stack
     *
     * @return list<mixed>
     */
    private function resolveRepeat(mixed $spec, array $stack): array
    {
        if (!\is_array($spec) || !\array_key_exists('item', $spec) || !isset($spec['count']) || !\is_int($spec['count']) || $spec['count'] < 0) {
            throw new \InvalidArgumentException('Invalid !repeat: expected a mapping with an integer "count" (>= 0) and an "item".');
        }

        $items = [];
        for ($i = 1; $i <= $spec['count']; ++$i) {
            $items[] = $this->doResolve($this->substituteIndex($spec['item'], $i), $stack);
        }

        return $items;
    }

    private function substituteIndex(mixed $value, int $index): mixed
    {
        if ($value instanceof TaggedValue) {
            return new TaggedValue($value->getTag(), $this->substituteIndex($value->getValue(), $index));
        }

        if (\is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->substituteIndex($item, $index), $value);
        }

        return \is_string($value) ? str_replace('{i}', (string) $index, $value) : $value;
    }
}
