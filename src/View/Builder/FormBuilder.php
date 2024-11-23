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

use IQ2i\StoriaBundle\View\Dto\Variant;
use IQ2i\StoriaBundle\View\Dto\View;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class FormBuilder extends AbstractBuilder
{
    public function __construct(
        string $defaultPath,
        Environment $twig,
        private readonly FormFactoryInterface $formFactory,
    ) {
        parent::__construct($defaultPath, $twig);
    }

    public function supports(string $path, array $config): bool
    {
        return \array_key_exists('form', $config) && class_exists($config['form']);
    }

    public function build(Request $request, string $path, array $config): ?View
    {
        $config['variants'] = [
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

        $variantPath = $request->query->get('variant');
        if (isset($config['variants'][$variantPath])) {
            $variantConfig = $config['variants'][$variantPath];

            $form = $this->formFactory->createNamed('field', $config['form'], null, array_merge([
                'label' => 'Label',
                'help' => 'Help text',
            ], $variantConfig['options'], $config['options']));

            if ($variantConfig['with_errors']) {
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
            ucfirst(strtolower(trim((string) preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $name)))),
            $variantPath === $name
        ), array_keys($config['variants']));

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
