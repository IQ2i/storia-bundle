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

use Symfony\UX\TwigComponent\Twig\PropsNode;
use Twig\Environment;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\Node\Expression\Unary\PosUnary;
use Twig\Node\Node;
use Twig\Node\TypesNode;
use Twig\Source;

/**
 * Reads the {% props %} and {% types %} tags of a template through the Twig AST: nothing is compiled nor executed.
 *
 * PropsNode is internal to ux-twig-component, but its own debug:twig-component command reads it the same way.
 * The "##" documentation comments need Twig 3.29, and PropsNode::getPropDocumentation() ux-twig-component 3.5.
 *
 * @internal
 */
class PropsExtractor
{
    /** @var array<string, array<string, PropDefinition>> */
    private array $props = [];

    /** @var array<string, array<string, TypeDefinition>> */
    private array $types = [];

    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @return array<string, PropDefinition>
     */
    public function extract(string $templateName): array
    {
        if (isset($this->props[$templateName])) {
            return $this->props[$templateName];
        }

        $props = [];
        foreach ($this->find($this->parse($templateName), PropsNode::class) as $node) {
            foreach ($node->getAttribute('names') as $name) {
                // @phpstan-ignore function.alreadyNarrowedType (the method is missing on ux-twig-component < 3.5)
                $documentation = method_exists($node, 'getPropDocumentation') ? $node->getPropDocumentation($name) : null;
                $directive = ControlDirective::parse($documentation);

                if (!$node->hasNode($name)) {
                    $props[$name] = new PropDefinition(directive: $directive);
                    continue;
                }

                [$isConstant, $value] = $this->evaluate($node->getNode($name));
                $props[$name] = new PropDefinition('props', $isConstant ? $value : null, $isConstant, !$isConstant, null, $directive);
            }
        }

        return $this->props[$templateName] = $props;
    }

    /**
     * @param string $code the source of the template, which may not be reachable through the Twig loader
     *
     * @return array<string, TypeDefinition>
     */
    public function types(string $code): array
    {
        $key = md5($code);
        if (isset($this->types[$key])) {
            return $this->types[$key];
        }

        $types = [];
        foreach ($this->find($this->parse(null, $code), TypesNode::class) as $node) {
            foreach ($node as $name => $type) {
                // @phpstan-ignore function.alreadyNarrowedType (the method is missing on Twig < 3.29)
                $documentation = method_exists($type, 'getDocumentation') ? $type->getDocumentation() : null;
                $types[(string) $name] = new TypeDefinition($type->getAttribute('type'), $type->getAttribute('optional'), ControlDirective::parse($documentation));
            }
        }

        return $this->types[$key] = $types;
    }

    private function parse(?string $templateName, ?string $code = null): Node
    {
        if (null === $code) {
            $source = $this->twig->getLoader()->getSourceContext((string) $templateName);
            $source = new Source($source->getCode(), $source->getName(), $source->getPath());
        } else {
            $source = new Source($code, '__storia_types__');
        }

        return $this->twig->parse($this->twig->tokenize($source));
    }

    /**
     * @template T of Node
     *
     * @param class-string<T> $class
     *
     * @return iterable<T>
     */
    private function find(Node $node, string $class): iterable
    {
        if ($node instanceof $class) {
            yield $node;

            return;
        }

        foreach ($node as $child) {
            yield from $this->find($child, $class);
        }
    }

    /**
     * @return array{0: bool, 1: mixed} whether the node is a constant, and its value
     */
    private function evaluate(Node $node): array
    {
        if ($node instanceof ConstantExpression) {
            return [true, $node->getAttribute('value')];
        }

        if (($node instanceof NegUnary || $node instanceof PosUnary) && $node->hasNode('node')) {
            [$isConstant, $value] = $this->evaluate($node->getNode('node'));
            if ($isConstant && (\is_int($value) || \is_float($value))) {
                return [true, $node instanceof NegUnary ? -$value : $value];
            }
        }

        if ($node instanceof ArrayExpression) {
            $array = [];
            foreach ($node->getKeyValuePairs() as $pair) {
                [$keyIsConstant, $key] = $this->evaluate($pair['key']);
                [$valueIsConstant, $value] = $this->evaluate($pair['value']);
                if (!$keyIsConstant || !$valueIsConstant) {
                    return [false, null];
                }

                $array[$key] = $value;
            }

            return [true, $array];
        }

        return [false, null];
    }
}
