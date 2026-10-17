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

namespace IQ2i\StoriaBundle;

use IQ2i\StoriaBundle\Catalog\CatalogDescriber;
use IQ2i\StoriaBundle\Command\ExportCommand;
use IQ2i\StoriaBundle\Command\ListCommand;
use IQ2i\StoriaBundle\Command\RenderCommand;
use IQ2i\StoriaBundle\Command\TestCommand;
use IQ2i\StoriaBundle\Command\WatchCommand;
use IQ2i\StoriaBundle\Config\YamlPreProcessor;
use IQ2i\StoriaBundle\Controller\ChangesController;
use IQ2i\StoriaBundle\Controller\IframeController;
use IQ2i\StoriaBundle\Controller\ViewController;
use IQ2i\StoriaBundle\Controls\ComponentIntrospector;
use IQ2i\StoriaBundle\Controls\ControlsBuilder;
use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\DependencyInjection\Compiler\ArgResolverPass;
use IQ2i\StoriaBundle\DependencyInjection\Compiler\ProfilerPass;
use IQ2i\StoriaBundle\Export\AssetCollector;
use IQ2i\StoriaBundle\Export\StaticExporter;
use IQ2i\StoriaBundle\Export\UrlRewriter;
use IQ2i\StoriaBundle\Menu\MenuBuilder;
use IQ2i\StoriaBundle\Test\Snapshot\SnapshotStore;
use IQ2i\StoriaBundle\Test\VariantRunner;
use IQ2i\StoriaBundle\Twig\ViewExtension;
use IQ2i\StoriaBundle\Url\RouterLinkGenerator;
use IQ2i\StoriaBundle\View\Builder\ComponentBuilder;
use IQ2i\StoriaBundle\View\Builder\FormBuilder;
use IQ2i\StoriaBundle\View\ViewBuilder;
use IQ2i\StoriaBundle\View\ViewFinder;
use IQ2i\StoriaBundle\View\ViewRenderer;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class IQ2iStoriaBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();

        $rootNode
            ->children()
                ->scalarNode('default_path')
                    ->defaultValue('%kernel.project_dir%/storia')
                ->end()
                ->booleanNode('enabled')->defaultNull()->info('Defaults to kernel.debug')->end()
                ->arrayNode('watch')
                    ->scalarPrototype()->end()
                    ->defaultValue(['%kernel.project_dir%/assets', '%kernel.project_dir%/templates'])
                ->end()
                ->arrayNode('export')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('output_dir')
                            ->defaultValue('%kernel.project_dir%/var/storia/export')
                        ->end()
                        ->scalarNode('public_dir')
                            ->defaultValue('%kernel.project_dir%/public')
                        ->end()
                        ->arrayNode('assets')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('test')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('snapshot_path')
                            ->defaultValue('%kernel.project_dir%/tests/storia/snapshots')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $builder->setParameter('iq2i_storia.default_path', $config['default_path']);
        $builder->setParameter('iq2i_storia.enabled', $config['enabled'] ?? '%kernel.debug%');
        $builder->setParameter('iq2i_storia.watch', $config['watch']);
        $builder->setParameter('iq2i_storia.export.output_dir', $config['export']['output_dir']);
        $builder->setParameter('iq2i_storia.export.public_dir', $config['export']['public_dir']);
        $builder->setParameter('iq2i_storia.export.assets', $config['export']['assets']);
        $builder->setParameter('iq2i_storia.test.snapshot_path', $config['test']['snapshot_path']);

        $builder->register(ChangesController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                '%kernel.project_dir%',
            ]);

        $builder->register(WatchCommand::class)
            ->addTag('console.command')
            ->setArguments([
                '%iq2i_storia.default_path%',
                '%iq2i_storia.watch%',
                '%kernel.project_dir%',
            ]);

        $builder->register(UrlRewriter::class);
        $builder->register(AssetCollector::class)
            ->setArguments([
                '%iq2i_storia.export.public_dir%',
                '%iq2i_storia.export.assets%',
                new Reference('http_kernel'),
                new Reference(UrlRewriter::class),
                new Reference('iq2i_storia.filesystem'),
                new Reference('profiler', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ]);
        $builder->register('iq2i_storia.filesystem', Filesystem::class);
        $builder->register(VariantRunner::class)
            ->setArguments([
                new Reference(ViewFinder::class),
                new Reference(ViewBuilder::class),
            ]);
        $builder->register(SnapshotStore::class)
            ->setArguments([
                '%iq2i_storia.test.snapshot_path%',
                new Reference('iq2i_storia.filesystem'),
            ]);
        $builder->register(TestCommand::class)
            ->addTag('console.command')
            ->setArguments([
                new Reference(VariantRunner::class),
                new Reference(SnapshotStore::class),
            ]);
        $builder->register(CatalogDescriber::class)
            ->setArguments([
                '%iq2i_storia.default_path%',
                new Reference(VariantRunner::class),
                new Reference(YamlPreProcessor::class),
                new Reference(DataResolver::class),
            ]);
        $builder->register(ListCommand::class)
            ->addTag('console.command')
            ->setArguments([
                new Reference(CatalogDescriber::class),
            ]);
        $builder->register(RenderCommand::class)
            ->addTag('console.command')
            ->setArguments([
                new Reference(ViewBuilder::class),
            ]);
        $builder->register(StaticExporter::class)
            ->setArguments([
                new Reference(VariantRunner::class),
                new Reference(MenuBuilder::class),
                new Reference(ViewRenderer::class),
                new Reference(UrlRewriter::class),
                new Reference(AssetCollector::class),
                new Reference('iq2i_storia.filesystem'),
                __DIR__.'/../public',
            ]);
        $builder->register(ExportCommand::class)
            ->addTag('console.command')
            ->setArguments([
                new Reference(StaticExporter::class),
                '%iq2i_storia.export.output_dir%',
            ]);

        $builder->register(IframeController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                new Reference(ViewBuilder::class),
                new Reference(ViewRenderer::class),
            ]);
        $builder->register(ViewController::class)
            ->addTag('controller.service_arguments')
            ->setArguments([
                new Reference(ViewBuilder::class),
                new Reference(ViewRenderer::class),
                new Reference(MenuBuilder::class),
                new Reference(RouterLinkGenerator::class),
            ]);

        $builder->register(ViewRenderer::class)
            ->setArguments([
                new Reference('twig'),
            ]);
        $builder->register(ViewFinder::class)
            ->setArguments([
                '%iq2i_storia.default_path%',
            ]);
        $builder->register(RouterLinkGenerator::class)
            ->setArguments([
                new Reference('router'),
                new Reference('assets.packages'),
            ]);

        if (class_exists(\Symfony\UX\TwigComponent\ComponentFactory::class)) {
            $builder->register(ComponentBuilder::class)
                ->addTag('iq2i_storia.builder')
                ->setArguments([
                    '%iq2i_storia.default_path%',
                    new Reference('twig'),
                    '%kernel.project_dir%',
                    new Reference('ux.twig_component.component_template_finder'),
                    new Reference('ux.twig_component.component_factory'),
                    new Reference(DataResolver::class),
                    new Reference(ControlsBuilder::class),
                    new Reference(ComponentIntrospector::class),
                ]);
        }

        if (interface_exists(FormFactoryInterface::class)) {
            $builder->register(FormBuilder::class)
                ->addTag('iq2i_storia.builder')
                ->setArguments([
                    '%iq2i_storia.default_path%',
                    new Reference('twig'),
                    '%kernel.project_dir%',
                    new Reference('form.factory'),
                    new Reference('form.registry'),
                    new Reference(DataResolver::class),
                ]);
        }

        $builder->register(MenuBuilder::class)
            ->setArguments([
                '%iq2i_storia.default_path%',
                new Reference(ViewBuilder::class),
            ]);

        $builder->register(ViewExtension::class)
            ->addTag('twig.extension')
            ->setArguments([
                '%iq2i_storia.default_path%',
                new Reference(YamlPreProcessor::class),
                new Reference(DataResolver::class),
            ]);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ArgResolverPass());
        $container->addCompilerPass(new ProfilerPass());
    }
}
