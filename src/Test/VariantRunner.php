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

namespace IQ2i\StoriaBundle\Test;

use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use IQ2i\StoriaBundle\View\ViewBuilder;
use IQ2i\StoriaBundle\View\ViewFinder;

/**
 * Iterates over every variant of the catalog, shared by storia:export and storia:test.
 */
readonly class VariantRunner
{
    /** A variant name must be usable as a file name. */
    public const string SAFE_NAME = '/^[^\/\\\\\0.][^\/\\\\\0]*$/';

    public function __construct(
        private ViewFinder $viewFinder,
        private ViewBuilder $viewBuilder,
    ) {
    }

    /**
     * Lists the variants of each view without rendering them. A view that cannot be loaded
     * is mapped to the error instead of its variants.
     *
     * @return array<string, list<string>|\Throwable>
     */
    public function findVariants(?string $filter = null): array
    {
        $variantsByView = [];
        foreach ($this->viewFinder->findPaths() as $path) {
            try {
                $view = $this->viewBuilder->create($path, null);
            } catch (\Throwable $e) {
                if ($this->matches($path, null, $filter)) {
                    $variantsByView[$path] = $e;
                }

                continue;
            }

            $names = array_map(static fn (Variant $variant): string => $variant->getPath(), $view?->getVariants() ?? []);
            $variants = array_values(array_filter($names, fn (string $variant): bool => $this->matches($path, $variant, $filter)));

            if ([] !== $variants || ([] === $names && $this->matches($path, null, $filter))) {
                $variantsByView[$path] = $variants;
            }
        }

        return $variantsByView;
    }

    /**
     * Renders every variant and hands the outcome to the callback, whatever it is: the caller
     * decides whether an error stops the run. An exception thrown by the callback is not caught.
     * For a view that failed to load, the callback receives a null variant.
     *
     * @param array<string, list<string>|\Throwable>              $variantsByView
     * @param callable(string, ?string, ?View, ?\Throwable): void $onVariant
     */
    public function run(array $variantsByView, callable $onVariant): VariantRunResult
    {
        $variantCount = 0;
        $failures = [];

        foreach ($variantsByView as $path => $variants) {
            if ($variants instanceof \Throwable) {
                $failures[] = new VariantFailure($path, null, $variants);
                $onVariant($path, null, null, $variants);

                continue;
            }

            foreach ($variants as $variant) {
                ++$variantCount;

                $view = null;
                $error = null;
                try {
                    $view = $this->viewBuilder->create($path, $variant)
                        ?? throw new \LogicException(\sprintf('View "%s" disappeared during the run.', $path));
                } catch (\Throwable $e) {
                    $error = $e;
                    $failures[] = new VariantFailure($path, $variant, $e);
                }

                $onVariant($path, $variant, $view, $error);
            }
        }

        return new VariantRunResult(\count($variantsByView), $variantCount, $failures);
    }

    private function matches(string $path, ?string $variant, ?string $filter): bool
    {
        if (null === $filter || '' === $filter) {
            return true;
        }

        return str_contains($path, $filter) || (null !== $variant && str_contains($path.':'.$variant, $filter));
    }
}
