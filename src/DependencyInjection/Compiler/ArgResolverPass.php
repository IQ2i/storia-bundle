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

use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;

class ArgResolverPass implements CompilerPassInterface
{
    private const string METHOD_PATTERN = '/^([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)::[\w]+$/';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ArgResolver::class)) {
            return;
        }

        $storiaPath = (string) $container->getParameter('iq2i_storia.default_path');

        $classNames = $this->collectClassNamesFromYamlFiles($storiaPath);

        $services = [];
        foreach ($classNames as $className) {
            $reference = $this->resolveService($container, $className);
            if (null !== $reference) {
                $services[$className] = $reference;
            }
        }

        $definition = $container->getDefinition(ArgResolver::class);
        $definition->setArgument(0, new ServiceLocatorArgument($services));
    }

    /**
     * Parses all storia YAML files and extracts class names from "ClassName::method" references.
     *
     * @return list<string>
     */
    private function collectClassNamesFromYamlFiles(string $storiaPath): array
    {
        if (!is_dir($storiaPath)) {
            return [];
        }

        $classNames = [];

        $preProcessor = new YamlPreProcessor();

        $finder = (new Finder())->files()->name(['*.yaml', '*.yml'])->in($storiaPath);
        foreach ($finder as $file) {
            // Compute the path relative to $storiaPath (without extension) to use with the pre-processor.
            $relativeName = $file->getRelativePathname();
            $relativePath = substr($relativeName, 0, -(\strlen($file->getExtension()) + 1));

            try {
                $data = $preProcessor->process($storiaPath, $relativePath);
            } catch (\Throwable) {
                continue;
            }

            $this->extractClassNames($data, $classNames);
        }

        return array_unique($classNames);
    }

    /**
     * Recursively walks a parsed YAML value and collects class names from "ClassName::method" strings.
     *
     * @param array<string, string> $classNames
     */
    private function extractClassNames(mixed $value, array &$classNames): void
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                $this->extractClassNames($item, $classNames);
            }

            return;
        }

        if (\is_string($value) && preg_match(self::METHOD_PATTERN, $value, $matches)) {
            $classNames[] = $matches[1];
        }
    }

    /**
     * Resolves a class name to a service reference, checking aliases first then definitions.
     */
    private function resolveService(ContainerBuilder $container, string $className): ?Reference
    {
        try {
            if (!class_exists($className) && !interface_exists($className)) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        // Check FQCN aliases first (covers Sylius factories, autowiring aliases, etc.)
        if ($container->hasAlias($className)) {
            $targetId = (string) $container->getAlias($className);
            while ($container->hasAlias($targetId)) {
                $targetId = (string) $container->getAlias($targetId);
            }

            if ($container->hasDefinition($targetId) && !$container->getDefinition($targetId)->isAbstract()) {
                return new Reference($targetId);
            }
        }

        // Check if a definition exists with that exact ID (standard autowiring)
        if ($container->hasDefinition($className) && !$container->getDefinition($className)->isAbstract()) {
            return new Reference($className);
        }

        // Fallback: search definitions by class name
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || $definition->getClass() !== $className) {
                continue;
            }

            return new Reference($id);
        }

        return null;
    }
}
