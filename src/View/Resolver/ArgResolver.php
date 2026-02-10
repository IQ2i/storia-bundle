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

namespace IQ2i\StoriaBundle\View\Resolver;

use Psr\Container\ContainerInterface;

readonly class ArgResolver
{
    private const string METHOD_PATTERN = '/^([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)::([\w]+)$/';

    public function __construct(
        private ContainerInterface $serviceLocator,
    ) {
    }

    /**
     * Resolves arguments by detecting and calling methods referenced as "ClassName::methodName".
     *
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function resolve(array $args): array
    {
        $resolved = [];
        foreach ($args as $key => $value) {
            $resolved[$key] = $this->resolveValue($value);
        }

        return $resolved;
    }

    /**
     * Recursively resolves a value, handling arrays and method references.
     */
    private function resolveValue(mixed $value): mixed
    {
        // Handle arrays recursively
        if (\is_array($value)) {
            return array_map(fn ($item) => $this->resolveValue($item), $value);
        }

        // Only strings can be method references
        if (!\is_string($value)) {
            return $value;
        }

        // Check if the value matches the pattern "ClassName::methodName"
        if (!preg_match(self::METHOD_PATTERN, $value, $matches)) {
            return $value;
        }

        [, $className, $methodName] = $matches;

        return $this->callMethod($className, $methodName, $value);
    }

    /**
     * Calls a method on a class (static or instance method).
     *
     * @throws \RuntimeException If the class or method doesn't exist or isn't accessible
     */
    private function callMethod(string $className, string $methodName, string $originalValue): mixed
    {
        // Validate that the class exists
        if (!class_exists($className)) {
            throw new \RuntimeException(\sprintf('Cannot resolve argument "%s": class "%s" does not exist.', $originalValue, $className));
        }

        // Use reflection to check if the method exists
        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->hasMethod($methodName)) {
            throw new \RuntimeException(\sprintf('Cannot resolve argument "%s": method "%s" does not exist in class "%s".', $originalValue, $methodName, $className));
        }

        $reflectionMethod = $reflectionClass->getMethod($methodName);

        // Check if the method is public
        if (!$reflectionMethod->isPublic()) {
            throw new \RuntimeException(\sprintf('Cannot resolve argument "%s": method "%s::%s" is not public.', $originalValue, $className, $methodName));
        }

        // If the method is static, call it directly
        if ($reflectionMethod->isStatic()) {
            return $reflectionMethod->invoke(null);
        }

        // For instance methods, try to get the service from the container
        if (!$this->serviceLocator->has($className)) {
            throw new \RuntimeException(\sprintf('Cannot resolve argument "%s": method "%s::%s" is not static and class is not available in the service container.', $originalValue, $className, $methodName));
        }

        try {
            $instance = $this->serviceLocator->get($className);
        } catch (\Throwable $throwable) {
            throw new \RuntimeException(\sprintf('Cannot resolve argument "%s": failed to get service "%s" from container: %s', $originalValue, $className, $throwable->getMessage()), 0, $throwable);
        }

        return $reflectionMethod->invoke($instance);
    }
}
