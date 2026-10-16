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

namespace IQ2i\StoriaBundle\Controls;

use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\ComponentMetadata;

/**
 * Everything a component declares, read from its template and its PHP class without instantiating it.
 */
readonly class ComponentIntrospector
{
    public function __construct(
        private PropsExtractor $propsExtractor,
    ) {
    }

    /**
     * The props of the {% props %} tag, then the public properties and the mount() parameters of the class.
     *
     * @return array<string, PropDefinition>
     */
    public function props(ComponentMetadata $metadata): array
    {
        $props = $this->propsExtractor->extract($metadata->getTemplate());
        if ($metadata->isAnonymous()) {
            return $props;
        }

        $class = new \ReflectionClass($metadata->getClass());
        if ($metadata->isPublicPropsExposed()) {
            foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                $props[$property->getName()] = new PropDefinition(
                    'class',
                    $property->hasDefaultValue() ? $property->getDefaultValue() : null,
                    $property->hasDefaultValue(),
                    false,
                    $property->getType(),
                    ControlDirective::parse($property->getDocComment() ?: null),
                );
            }
        }

        if ($class->hasMethod('mount')) {
            $mount = $class->getMethod('mount');
            foreach ($mount->getParameters() as $parameter) {
                $props[$parameter->getName()] = new PropDefinition(
                    'class',
                    $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                    $parameter->isDefaultValueAvailable(),
                    false,
                    $parameter->getType(),
                    ControlDirective::parse($this->paramDocumentation($mount, $parameter->getName())),
                );
            }
        }

        return $props;
    }

    /**
     * The names under which #[ExposeInTemplate] properties and methods are available in the template.
     *
     * @return list<string>
     */
    public function exposedNames(ComponentMetadata $metadata): array
    {
        if ($metadata->isAnonymous()) {
            return [];
        }

        $names = [];
        $class = new \ReflectionClass($metadata->getClass());
        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes(ExposeInTemplate::class) as $attribute) {
                $names[] = $attribute->newInstance()->name ?? $property->getName();
            }
        }

        foreach ($class->getMethods() as $method) {
            foreach ($method->getAttributes(ExposeInTemplate::class) as $attribute) {
                $names[] = $attribute->newInstance()->name ?? lcfirst(str_replace('get', '', $method->getName()));
            }
        }

        return $names;
    }

    /**
     * The text following "@param <type> $name" in the docblock of the method.
     */
    private function paramDocumentation(\ReflectionMethod $method, string $name): ?string
    {
        $docComment = $method->getDocComment();
        if (false === $docComment || !preg_match('/@param\s+\S+\s+\$'.preg_quote($name, '/').'\s+([^\n]*)/', $docComment, $matches)) {
            return null;
        }

        return trim(rtrim($matches[1], '*/ '));
    }
}
