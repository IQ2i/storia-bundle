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

namespace IQ2i\StoriaBundle\Test\Snapshot;

/**
 * Only line endings and trailing whitespace are normalized: reformatting the HTML would hide real regressions.
 *
 * @internal
 */
final class SnapshotNormalizer
{
    public static function normalize(string $html): string
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $html));
        $lines = array_map(rtrim(...), $lines);

        return rtrim(implode("\n", $lines))."\n";
    }
}
