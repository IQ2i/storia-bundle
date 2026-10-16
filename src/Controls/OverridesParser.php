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

namespace IQ2i\StoriaBundle\Controls;

use Symfony\Component\HttpFoundation\Request;

final class OverridesParser
{
    private const int MAX_LENGTH = 65536;

    private const int MAX_DEPTH = 16;

    /**
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request): array
    {
        // all() avoids the BadRequestException thrown by get() on "overrides[]=x"
        $raw = $request->query->all()['overrides'] ?? null;
        if (!\is_string($raw) || '' === $raw || \strlen($raw) > self::MAX_LENGTH) {
            return [];
        }

        try {
            $overrides = json_decode($raw, true, self::MAX_DEPTH, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($overrides) && !array_is_list($overrides) ? $overrides : [];
    }
}
