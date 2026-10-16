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

/**
 * @internal
 */
readonly class UrlRewriter
{
    private const string ASSET_TAGS = 'link|script|img|source|video|audio|track|embed|input|object';

    private const string LINK_RELS = 'stylesheet|icon|preload|modulepreload|prefetch|manifest|mask-icon';

    public static function prefixFor(string $relativeFile): string
    {
        return str_repeat('../', substr_count($relativeFile, '/'));
    }

    public function rewriteHtml(string $html, string $prefix): string
    {
        return $this->mapHtml($html, fn (string $url): string => $this->prefix($url, $prefix));
    }

    public function rewriteCss(string $css, string $prefix): string
    {
        return $this->mapCss($css, fn (string $url): string => $this->prefix($url, $prefix));
    }

    /**
     * @return list<string>
     */
    public function extractHtmlUrls(string $html): array
    {
        $urls = [];
        $this->mapHtml($html, static function (string $url) use (&$urls): string {
            $urls[] = $url;

            return $url;
        });

        return array_values(array_unique($urls));
    }

    /**
     * @return list<string>
     */
    public function extractCssUrls(string $css): array
    {
        $urls = [];
        preg_match_all('/url\(\s*(["\']?)([^"\')\s]+)\1\s*\)|@import\s+(["\'])([^"\']+)\3/i', $css, $matches, \PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = '' !== ($match[2] ?? '') ? $match[2] : ($match[4] ?? '');
            if ($this->isLocal($url)) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @param callable(string): string $map
     */
    private function mapHtml(string $html, callable $map): string
    {
        $html = (string) preg_replace_callback(
            '/<('.self::ASSET_TAGS.')\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>/i',
            fn (array $tag): string => $this->mapTag($tag[1], $tag[2], $map),
            $html
        );

        $html = (string) preg_replace_callback(
            '/(\sstyle\s*=\s*)(["\'])(.*?)\2/is',
            fn (array $attribute): string => $attribute[1].$attribute[2].$this->mapCss($attribute[3], $map).$attribute[2],
            $html
        );

        $html = (string) preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style>)/is',
            fn (array $block): string => $block[1].$this->mapCss($block[2], $map).$block[3],
            $html
        );

        return (string) preg_replace_callback(
            '/(<script\b[^>]*\btype\s*=\s*["\']importmap["\'][^>]*>)(.*?)(<\/script>)/is',
            fn (array $block): string => $block[1].$this->mapJsonStrings($block[2], $map).$block[3],
            $html
        );
    }

    /**
     * @param callable(string): string $map
     */
    private function mapTag(string $name, string $attributes, callable $map): string
    {
        if ('link' === strtolower($name) && !preg_match('/\srel\s*=\s*["\'][^"\']*\b(?:'.self::LINK_RELS.')\b/i', $attributes)) {
            return '<'.$name.$attributes.'>';
        }

        $attributes = (string) preg_replace_callback(
            '/(\s)(src|href|srcset|poster|data)(\s*=\s*)(["\'])(.*?)\4/is',
            function (array $attribute) use ($map): string {
                $value = 'srcset' === strtolower($attribute[2]) ? $this->mapSrcset($attribute[5], $map) : $this->mapUrl($attribute[5], $map);

                return $attribute[1].$attribute[2].$attribute[3].$attribute[4].$value.$attribute[4];
            },
            $attributes
        );

        return '<'.$name.$attributes.'>';
    }

    /**
     * @param callable(string): string $map
     */
    private function mapSrcset(string $srcset, callable $map): string
    {
        $candidates = array_map(function (string $candidate) use ($map): string {
            $parts = preg_split('/\s+/', trim($candidate), 2);

            return $this->mapUrl($parts[0], $map).(isset($parts[1]) ? ' '.$parts[1] : '');
        }, explode(',', $srcset));

        return implode(', ', $candidates);
    }

    /**
     * @param callable(string): string $map
     */
    private function mapCss(string $css, callable $map): string
    {
        return (string) preg_replace_callback(
            '/url\(\s*(["\']?)([^"\')\s]+)\1\s*\)|@import\s+(["\'])([^"\']+)\3/i',
            function (array $match) use ($map): string {
                if ('' !== $match[2]) {
                    return 'url('.$match[1].$this->mapUrl($match[2], $map).$match[1].')';
                }

                return '@import '.$match[3].$this->mapUrl($match[4], $map).$match[3];
            },
            $css
        );
    }

    /**
     * @param callable(string): string $map
     */
    private function mapJsonStrings(string $json, callable $map): string
    {
        return (string) preg_replace_callback(
            '/"(\/(?!\/)[^"]*)"/',
            static fn (array $match): string => '"'.$map($match[1]).'"',
            $json
        );
    }

    /**
     * @param callable(string): string $map
     */
    private function mapUrl(string $url, callable $map): string
    {
        return $this->isRootRelative($url) ? $map($url) : $url;
    }

    private function prefix(string $url, string $prefix): string
    {
        $relative = ltrim($url, '/');

        return '' === $prefix && '' === $relative ? './' : $prefix.$relative;
    }

    private function isRootRelative(string $url): bool
    {
        return str_starts_with($url, '/') && !str_starts_with($url, '//');
    }

    private function isLocal(string $url): bool
    {
        return '' !== $url
            && !str_starts_with($url, '#')
            && !str_starts_with($url, '//')
            && !preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url);
    }
}
