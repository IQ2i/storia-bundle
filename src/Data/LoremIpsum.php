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

/**
 * Deterministic filler text: the same spec always yields the same output.
 */
final class LoremIpsum
{
    private const array WORDS = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'do',
        'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua', 'enim',
        'ad', 'minim', 'veniam', 'quis', 'nostrud', 'exercitation', 'ullamco', 'laboris', 'nisi',
        'aliquip', 'ex', 'ea', 'commodo', 'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit',
        'voluptate', 'velit', 'esse', 'cillum', 'eu', 'fugiat', 'nulla', 'pariatur',
    ];

    private const int WORDS_PER_SENTENCE = 8;

    private const int SENTENCES_PER_PARAGRAPH = 4;

    /**
     * @param int $seed Shifts the text by $seed chunks of $count units, so that two specs with different seeds never share text
     */
    public static function fromSpec(string $spec, int $seed = 0): string
    {
        if (!preg_match('/^(\d+)(?:\s+(words?|sentences?|paragraphs?))?$/', trim($spec), $matches)) {
            throw new \InvalidArgumentException(\sprintf('Invalid !lorem spec "%s": expected a count optionally followed by "words", "sentences" or "paragraphs" (e.g. "12", "2 sentences").', $spec));
        }

        $count = (int) $matches[1];
        $offset = $seed * $count;

        return match (rtrim($matches[2] ?? 'words', 's')) {
            'sentence' => self::sentences($count, $offset),
            'paragraph' => self::paragraphs($count, $offset),
            default => self::words($count, $offset),
        };
    }

    public static function words(int $count, int $offset = 0): string
    {
        $words = [];
        for ($i = 0; $i < $count; ++$i) {
            $words[] = self::WORDS[($offset + $i) % \count(self::WORDS)];
        }

        return implode(' ', $words);
    }

    public static function sentences(int $count, int $offset = 0): string
    {
        $sentences = [];
        for ($i = 0; $i < $count; ++$i) {
            $sentences[] = ucfirst(self::words(self::WORDS_PER_SENTENCE, ($offset + $i) * self::WORDS_PER_SENTENCE)).'.';
        }

        return implode(' ', $sentences);
    }

    public static function paragraphs(int $count, int $offset = 0): string
    {
        $paragraphs = [];
        for ($i = 0; $i < $count; ++$i) {
            $paragraphs[] = self::sentences(self::SENTENCES_PER_PARAGRAPH, ($offset + $i) * self::SENTENCES_PER_PARAGRAPH);
        }

        return implode("\n\n", $paragraphs);
    }
}
