# UI Storia

## Overview

UI Storia is the design-system workbench for Symfony, published as `iq2i/storia-bundle` by IQ2i and sponsored by Mezcalito.

Your design system lives in your Symfony project, built by your integrators.

![UI Storia, the design-system workbench for Symfony](https://ui-storia.com/ui-storia-promo.png)

- **Real templates.** Unlike Fractal or Storybook, which rely on a port of Twig to JS, UI Storia renders the templates and Twig Components of your project, thus enabling the safe use of the latest Twig features available.
- **No PHP required.** An integrator writes an anonymous component and a YAML file of variants.
- **Variants are tests.** `storia:test` renders every variant and compares it with a snapshot.

[Read the documentation](https://ui-storia.com/docs/getting-started/) · [See the demo](https://ui-storia.com/demo/) · [Changelog](CHANGELOG.md)

## Requirements

| UI Storia | PHP    | Symfony          |
|-----------|--------|------------------|
| 0.x       | >= 8.3 | 6.4, 7.x and 8.x |

`symfony/ux-twig-component` and `symfony/form` are optional: install them to preview components and form types.

## Sponsors

<p align="center">
  <a target="_blank" href="https://www.mezcalito.fr">
    <img alt="Mezcalito - Agence Digitale à Grenoble depuis 2006" src="https://raw.githubusercontent.com/IQ2i/storia-bundle/main/doc/static/mezcalito.svg" width="300">
  </a>
</p>

## Issues and feature requests

Please report issues and request features at https://github.com/iq2i/storia-bundle/issues.

## License

This bundle is under the MIT license. For the whole copyright, see the LICENSE file distributed with this source code.