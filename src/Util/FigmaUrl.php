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
final class FigmaUrl
{
    private const string PATTERN = '#^https://(?:[\w.-]+\.)?figma\.com/([\w-]+)/([0-9a-zA-Z]{22,128})(?:[/?\#].*)?$#';

    private const string EMBED_HOST = 'embed.figma.com';

    private const string EMBED_HOST_NAME = 'ui-storia';

    public static function isValid(string $url): bool
    {
        return 1 === preg_match(self::PATTERN, $url);
    }

    /**
     * Turns a Figma link into its Embed Kit 2.0 URL: the embed host, dashed parameter names, no "embed_origin" and the "embed-host" of UI Storia.
     */
    public static function toEmbedUrl(string $url): string
    {
        $parts = parse_url($url);
        if (!self::isValid($url) || false === $parts) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a Figma URL.', $url));
        }

        $query = [];
        foreach (explode('&', $parts['query'] ?? '') as $pair) {
            if ('' === $pair) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $name = str_replace('_', '-', rawurldecode($name));
            if (\in_array($name, ['embed-origin', 'embed-host'], true)) {
                continue;
            }

            $query[$name] = rawurldecode($value);
        }

        $query['embed-host'] = self::EMBED_HOST_NAME;

        // The embed host only knows the current paths: legacy "/file/" links are served under "/design/"
        $path = preg_replace('#^/file/#', '/design/', $parts['path'] ?? '/');

        return 'https://'.self::EMBED_HOST.$path.'?'.http_build_query($query, '', '&', \PHP_QUERY_RFC3986);
    }
}
