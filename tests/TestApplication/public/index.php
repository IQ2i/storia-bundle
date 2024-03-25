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

use IQ2i\StoriaBundle\Tests\TestApplication\Kernel;

require_once dirname(__DIR__).'/../../vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel();
};
