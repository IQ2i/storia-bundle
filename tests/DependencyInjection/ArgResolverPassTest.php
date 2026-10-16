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

namespace IQ2i\StoriaBundle\Tests\DependencyInjection;

use IQ2i\StoriaBundle\DependencyInjection\Compiler\ArgResolverPass;
use IQ2i\StoriaBundle\Tests\Fixtures\ProductFactory;
use IQ2i\StoriaBundle\View\Resolver\ArgResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ArgResolverPassTest extends TestCase
{
    public function testClassReferencedOnlyThroughDataTagIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('iq2i_storia.default_path', __DIR__.'/../Fixtures/storia_data');
        $container->setDefinition(ArgResolver::class, new Definition(ArgResolver::class, [null]));
        $container->setDefinition(ProductFactory::class, new Definition(ProductFactory::class));

        (new ArgResolverPass())->process($container);

        /** @var ServiceLocatorArgument $locator */
        $locator = $container->getDefinition(ArgResolver::class)->getArgument(0);
        $this->assertArrayHasKey(ProductFactory::class, $locator->getValues());
    }
}
