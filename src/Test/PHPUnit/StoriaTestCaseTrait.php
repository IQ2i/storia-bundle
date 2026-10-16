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

use IQ2i\StoriaBundle\Test\Snapshot\SnapshotNormalizer;
use IQ2i\StoriaBundle\Test\Snapshot\SnapshotStore;
use IQ2i\StoriaBundle\View\Dto\View;
use IQ2i\StoriaBundle\View\ViewBuilder;

/**
 * To be used in a KernelTestCase.
 */
trait StoriaTestCaseTrait
{
    protected function renderVariant(string $path, string $variant): View
    {
        $view = static::getContainer()->get(ViewBuilder::class)->create($path, $variant);
        if (null === $view) {
            throw new \LogicException(\sprintf('View "%s" does not exist.', $path));
        }

        return $view;
    }

    protected function assertVariantRenders(string $path, string $variant): View
    {
        try {
            $view = $this->renderVariant($path, $variant);
        } catch (\Throwable $throwable) {
            self::fail(\sprintf('Variant "%s:%s" failed to render: %s: %s', $path, $variant, $throwable::class, $throwable->getMessage()));
        }

        self::assertNotNull($view->getHtmlContent(), \sprintf('Variant "%s:%s" does not exist.', $path, $variant));

        return $view;
    }

    /**
     * A missing snapshot is written and the test is marked incomplete, so that it is reviewed and committed.
     */
    protected function assertVariantMatchesSnapshot(string $path, string $variant): void
    {
        $html = SnapshotNormalizer::normalize((string) $this->assertVariantRenders($path, $variant)->getHtmlContent());

        $store = static::getContainer()->get(SnapshotStore::class);
        if (!$store->exists($path, $variant)) {
            $store->write($path, $variant, $html);
            self::markTestIncomplete(\sprintf('Snapshot of "%s:%s" did not exist, it was written.', $path, $variant));
        }

        self::assertSame($store->read($path, $variant), $html, \sprintf('Variant "%s:%s" differs from its snapshot.', $path, $variant));
    }
}
