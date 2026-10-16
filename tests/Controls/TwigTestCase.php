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

namespace IQ2i\StoriaBundle\Tests\Controls;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\Twig\PropsNode;
use Symfony\UX\TwigComponent\Twig\PropsTokenParser;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\TokenParser\TokenParserInterface;

abstract class TwigTestCase extends TestCase
{
    public static function requirePropDocumentation(): void
    {
        if (!method_exists(PropsNode::class, 'getPropDocumentation')) {
            self::markTestSkipped('Prop documentation needs ux-twig-component 3.5.');
        }
    }

    /**
     * @param array<string, string> $templates
     */
    protected function createTwig(array $templates): Environment
    {
        $twig = new Environment(new ArrayLoader($templates));
        $twig->addExtension(new class extends AbstractExtension {
            /**
             * @return list<TokenParserInterface>
             */
            public function getTokenParsers(): array
            {
                return [new PropsTokenParser()];
            }
        });

        return $twig;
    }
}
