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

namespace IQ2i\StoriaBundle\DependencyInjection\Compiler;

use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ArgResolverPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ArgResolver::class)) {
            return;
        }

        // Collect all public non-abstract services indexed by their class name
        $services = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isPublic() && !$definition->isAbstract() && null !== $definition->getClass()) {
                $class = $definition->getClass();
                if (class_exists($class)) {
                    $services[$class] = new Reference($id);
                }
            }
        }

        // Update the ArgResolver service to inject the ServiceLocator
        $definition = $container->getDefinition(ArgResolver::class);
        $definition->setArgument(0, new \Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument($services));
    }
}
