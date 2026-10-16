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

namespace IQ2i\StoriaBundle\Tests\View;

use IQ2i\StoriaBundle\View\ViewFinder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ViewFinderTest extends TestCase
{
    public function testFindsViewsOfComponentsAndPages(): void
    {
        $paths = (new ViewFinder(__DIR__.'/../TestApplication/storia'))->findPaths();

        $this->assertContains('components/badge', $paths);
        $this->assertContains('components/button/button', $paths);
        $this->assertContains('components/form/text', $paths);
        $this->assertContains('pages/homepage/homepage', $paths);
        $this->assertNotContains('_shared', $paths);
        $this->assertSame($paths, array_values(array_unique($paths)));

        $sorted = $paths;
        sort($sorted);
        $this->assertSame($sorted, $paths);
    }

    public function testToleratesMissingFolders(): void
    {
        $directory = sys_get_temp_dir().'/storia_finder_'.bin2hex(random_bytes(4));
        $filesystem = new Filesystem();
        $filesystem->mkdir($directory.'/pages');
        $filesystem->dumpFile($directory.'/pages/home.yml', '');

        try {
            $this->assertSame(['pages/home'], (new ViewFinder($directory))->findPaths());
        } finally {
            $filesystem->remove($directory);
        }
    }
}
