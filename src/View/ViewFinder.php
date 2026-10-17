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

namespace IQ2i\StoriaBundle\View;

use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
readonly class ViewFinder
{
    private const array FOLDERS = ['components', 'pages'];

    public function __construct(
        private string $defaultPath,
    ) {
    }

    /**
     * @return list<string>
     */
    public function findPaths(): array
    {
        $paths = [];
        foreach (self::FOLDERS as $folder) {
            if (!is_dir($this->defaultPath.'/'.$folder)) {
                continue;
            }

            foreach ((new Finder())->files()->in($this->defaultPath.'/'.$folder)->name(['*.yaml', '*.yml']) as $file) {
                $paths[] = $folder.'/'.preg_replace('/\.ya?ml$/', '', $file->getRelativePathname());
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths);

        return $paths;
    }
}
