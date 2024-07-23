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

use IQ2i\StoriaBundle\Config\ViewConfiguration;
use IQ2i\StoriaBundle\View\Builder\BuilderInterface;
use IQ2i\StoriaBundle\View\Dto\View;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

readonly class ViewBuilder
{
    public function __construct(
        private string $defaultPath,
        private iterable $builders,
    ) {
    }

    public function createFromRequest(Request $request): ?View
    {
        $path = $request->attributes->get('view');
        if (null === $path || !file_exists($this->defaultPath.'/'.$path.'.yaml')) {
            return null;
        }

        $config = (new Processor())->processConfiguration(
            new ViewConfiguration(),
            [Yaml::parse(file_get_contents($this->defaultPath.'/'.$path.'.yaml'))]
        );

        $name = $config['name'] ?? null;
        if (null === $name) {
            $name = pathinfo(str_replace('.yaml', '', $path), \PATHINFO_FILENAME);
            $name = ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name))));
        }

        /** @var BuilderInterface $builder */
        foreach ($this->builders as $builder) {
            if ($builder->supports($path, $config)) {
                return $builder->build($request, $path, $name, $config);
            }
        }

        throw new \LogicException(sprintf('Missing template for component "%s"', $path));
    }
}
