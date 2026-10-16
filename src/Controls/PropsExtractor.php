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
use Twig\Source;

/**
 * Reads the {% props %} tag of a template through the Twig AST: nothing is compiled nor executed.
 */
readonly class PropsExtractor
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * @return array<string, PropDefinition>
     */
    public function extract(string $templateName): array
    {
        if (!class_exists(PropsNode::class)) {
            return [];
        }

        $source = $this->twig->getLoader()->getSourceContext($templateName);
        $ast = $this->twig->parse($this->twig->tokenize(new Source($source->getCode(), $source->getName(), $source->getPath())));

        $props = [];
        foreach ($this->findPropsNodes($ast) as $node) {
            foreach ($node->getAttribute('names') as $name) {
                if (!$node->hasNode($name)) {
                    $props[$name] = new PropDefinition();
                    continue;
                }

                [$isConstant, $value] = $this->evaluate($node->getNode($name));
                $props[$name] = new PropDefinition($isConstant ? $value : null, $isConstant, !$isConstant);
            }
        }

        return $props;
    }

    /**
     * @return iterable<PropsNode>
     */
    private function findPropsNodes(Node $node): iterable
    {
        if ($node instanceof PropsNode) {
            yield $node;

            return;
        }

        foreach ($node as $child) {
            yield from $this->findPropsNodes($child);
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
