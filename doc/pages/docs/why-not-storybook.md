---
title: Why not Storybook?
layout: docs
menu:
  docs:
    weight: 30
---

Storybook and Fractal are excellent tools, built for JavaScript front-ends. UI Storia answers a narrower question: how do you document and test the Twig components of a Symfony application, with the people who write them?

## What a port of Twig breaks

Tools that run on Node cannot call your PHP application. To display a Twig template, they need a JavaScript implementation of Twig, or a bridge to the server. Components then diverge from what your application renders:

- Symfony UX components (`<twig:Button>`), `{% props %}`, and anonymous components are not plain Twig.
- Filters, functions and tests written in PHP, or coming from Symfony bundles (`path()`, `asset()`, `trans`, `ux_icon()`), do not exist on the JavaScript side.
- Form themes and Symfony forms need the PHP form component.
- Each new Twig feature must be supported by the port before you can use it.

UI Storia renders the templates of your project with your Twig environment. If it works in the catalog, it works in the application.

## Comparison

| | UI Storia | Storybook | Fractal |
|---|---|---|---|
| Rendering of Twig | Your Symfony Twig environment | JavaScript port or bridge to a server | JavaScript Twig implementation |
| Who writes the stories | Integrators, in YAML, without PHP or JS | Developers, in JavaScript | Integrators and developers, in YAML or JS configuration |
| Symfony integration | Twig Components, Symfony forms, form themes, services as data providers | Through extra layers | None |
| Runtime | PHP, inside your project | Node | Node |
| Maintenance | Follows your Symfony and Twig versions | A second toolchain to keep in sync | A second toolchain to keep in sync |
| Tests | One test per variant with `storia:test` | Test runner and add-ons | Not included |

Check the current state of each tool before choosing: they evolve, and this table describes the general approach of each.

## When another tool is a better fit

- Your design system is written in React, Vue or Web Components: use Storybook.
- You want visual regression testing of screenshots, or interaction tests: UI Storia only compares HTML.
- Your front-end is not a Symfony application.
