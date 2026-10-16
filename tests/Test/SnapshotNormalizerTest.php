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

namespace IQ2i\StoriaBundle\Tests\Test;

use IQ2i\StoriaBundle\Test\Snapshot\SnapshotNormalizer;
use PHPUnit\Framework\TestCase;

class SnapshotNormalizerTest extends TestCase
{
    public function testNormalizesLineEndings(): void
    {
        $this->assertSame("a\nb\nc\n", SnapshotNormalizer::normalize("a\r\nb\rc"));
    }

    public function testTrimsTrailingWhitespaceOfEachLine(): void
    {
        $this->assertSame("<p>\n  text\n</p>\n", SnapshotNormalizer::normalize("<p>  \n  text \t\n</p>"));
    }

    public function testEndsWithASingleNewline(): void
    {
        $this->assertSame("<p></p>\n", SnapshotNormalizer::normalize("<p></p>\n\n  \n"));
        $this->assertSame("\n", SnapshotNormalizer::normalize(''));
    }

    public function testKeepsIndentationAndInnerBlankLines(): void
    {
        $this->assertSame("<div>\n\n    <p></p>\n</div>\n", SnapshotNormalizer::normalize("<div>\n\n    <p></p>\n</div>"));
    }
}
