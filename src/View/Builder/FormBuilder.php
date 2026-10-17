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
use IQ2i\StoriaBundle\View\Dto\View;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Twig\Environment;

/**
 * @internal
 */
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
        string $projectDir,
        private readonly FormFactoryInterface $formFactory,
        private readonly FormRegistryInterface $formRegistry,
        private readonly DataResolver $dataResolver,
    ) {
        parent::__construct($defaultPath, $twig, $projectDir);
    }

    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('form', $config) && class_exists($config['form']);
    }

    public function build(string $path, ?string $variantPath, array $config, array $overrides = []): ?View
    {
        $variantConfigs = array_replace(self::VARIANTS, $config['variants'] ?? []);
        $twigContent = null;
        $htmlContent = null;

        if (null !== $variantPath && isset($variantConfigs[$variantPath])) {
            $variantConfig = $variantConfigs[$variantPath];

            $form = $this->formFactory->createNamed('field', $config['form'], null, array_merge([
                'label' => 'Label',
                'help' => 'Help text',
            ], $this->csrfOptions($config['form']), $this->dataResolver->resolve($config['options'] ?? []), $this->dataResolver->resolve($variantConfig['options'] ?? [])));

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

        $variants = $this->buildVariants($variantConfigs, $variantPath);

        return new View(
            $path,
            $twigContent,
            $htmlContent,
            null,
            null,
            $variants
        );
    }

    /**
     * The preview is never submitted: a token would change at every rendering, and cannot be generated outside of a request.
     *
     * @param class-string $formType
     *
     * @return array{csrf_protection?: false}
     */
    private function csrfOptions(string $formType): array
    {
        return $this->formRegistry->getType($formType)->getOptionsResolver()->isDefined('csrf_protection')
            ? ['csrf_protection' => false]
            : [];
    }
}
