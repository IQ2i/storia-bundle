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

namespace IQ2i\StoriaBundle\Util;

/**
 * @internal
 */
final class Label
{
    /**
     * Turns a file or variant name ("buttonGroup", "extra_large") into a label ("Button group", "Extra large").
     */
    public static function humanize(string $name): string
    {
        return ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name))));
    }
}
