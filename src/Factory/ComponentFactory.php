<?php

/*
 * This file is part of the Arqui project.
 *
 * (c) Loïc Sapone <loic@sapone.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace IQ2i\ArquiBundle\Factory;

use IQ2i\ArquiBundle\Dto\Component;
use IQ2i\ArquiBundle\Dto\Variant;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

use function Symfony\Component\String\u;

readonly class ComponentFactory
{
    public function __construct(
        private string $defaultPath,
        private Environment $twig,
    ) {
    }

    public function createFromRequest(Request $request): ?Component
    {
        $componentPath = $request->attributes->get('component');
        if (null === $componentPath) {
            return null;
        }

        $componentName = pathinfo(str_replace('.twig', '', (string) $componentPath), \PATHINFO_FILENAME);
        $componentName = ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $componentName))));

        $component = new Component($componentPath, $componentName);

        if (null === $request->query->get('variant')) {
            return $component;
        }

        $variantPath = $request->query->get('variant');
        $variantName = ucfirst(strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $variantPath))));

        $component->setVariant(new Variant($variantPath, $variantName));

        $content = file_get_contents($this->defaultPath.'/'.$component->getPath());
        preg_match('/{% block '.$component->getVariant()->getPath().' %}((?!{% endblock '.$component->getVariant()->getPath().' %}).*){% endblock '.$component->getVariant()->getPath().' %}/s', $content, $matches);
        $twig = $matches[1] ?? null;
        $component->setTwigContent($this->cleanSource($twig, true));

        $html = $this->twig->load($component->getPath())->renderBlock($component->getVariant()->getPath());
        $component->setHtmlContent($this->cleanSource($html));

        return $component;
    }

    private function cleanSource(string $source, bool $unindent = false): string
    {
        $lines = explode("\n", $source);
        if ($unindent) {
            $lines = array_map(static fn (string $line): string => substr($line, 4), $lines);
        }

        $source = u(implode("\n", $lines));

        return $source->trim()->toString();
    }
}
