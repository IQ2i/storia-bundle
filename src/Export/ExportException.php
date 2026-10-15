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

namespace IQ2i\StoriaBundle\Export;

final class ExportException extends \RuntimeException
{
    public static function forView(string $view, ?string $variant, \Throwable $previous): self
    {
        return new self(\sprintf('Variant "%s" failed: %s', $view.(null === $variant ? '' : ':'.$variant), $previous->getMessage()), 0, $previous);
    }

    public static function forAsset(string $url, string $source, string $reason, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Asset "%s" referenced by "%s" cannot be exported: %s', $url, $source, $reason), 0, $previous);
    }
}
