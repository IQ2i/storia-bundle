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

// Stands for a console whose storia:test finds a broken variant
echo "✗ components/broken:default\n    Twig\\Error\\LoaderError: Unable to find template\n\n1 variants, 0 passed, 0 new, 0 updated, 1 failed\n";
exit(1);
