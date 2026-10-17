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

namespace IQ2i\StoriaBundle\View\Builder;

use IQ2i\StoriaBundle\Data\DataResolver;
use IQ2i\StoriaBundle\Util\Label;
use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

class FormBuilder extends AbstractBuilder
{
    /** A form always has these three variants, whatever its YAML file says. */
    public const array VARIANTS = [
        'default' => [
            'options' => [],
            'with_errors' => false,
        ],
        'disabled' => [
            'options' => [
                'disabled' => true,
            ],
            'with_errors' => false,
        ],
        'error' => [
            'options' => [],
            'with_errors' => true,
        ],
    ];

    public function __construct(
        string $defaultPath,
        Environment $twig,
        private readonly FormFactoryInterface $formFactory,
        private readonly DataResolver $dataResolver,
    ) {
        parent::__construct($defaultPath, $twig);
    }

    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('form', $config) && class_exists($config['form']);
    }

    public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View
    {
        $variantConfigs = array_replace(self::VARIANTS, $config['variants'] ?? []);

        if (null !== $variantPath && isset($variantConfigs[$variantPath])) {
            $variantConfig = $variantConfigs[$variantPath];

            $form = $this->formFactory->createNamed('field', $config['form'], null, array_merge([
                'label' => 'Label',
                'help' => 'Help text',
            ], $this->dataResolver->resolve($config['options'] ?? []), $this->dataResolver->resolve($variantConfig['options'] ?? [])));

            if ($variantConfig['with_errors'] ?? false) {
                $form->addError(new FormError('Error message'));
                $form->submit([]);
            }

            $twigContent = $this->getTwigContent('@IQ2iStoria/components/form.html.twig');
            $htmlContent = $this->generateHtml($twigContent, [
                'form' => $form->createView(),
                'form_theme' => $config['form_theme'] ?? null,
            ]);
        }

        $variants = array_map(static fn (string $name): Variant => new Variant(
            $name,
            $variantConfigs[$name]['name'] ?? Label::humanize($name),
            $variantPath === $name
        ), array_keys($variantConfigs));

        return new View(
            $path,
            $twigContent ?? null,
            $htmlContent ?? null,
            null,
            null,
            $variants
        );
    }
}
