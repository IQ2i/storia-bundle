---
title: Workflow
layout: docs
menu:
  docs:
    weight: 20
---

UI Storia is built for a team where **integrators** and **developers** share the same templates: your design system lives in your Symfony project, built by your integrators.

![The integrator writes the component and its variants, the developer adds logic and tests, the designer reviews the export](/media/workflow.svg)

## Integrator first, then developer

1. **From the mockup to a component.** The integrator creates an anonymous component in `templates/components`, with Twig and CSS only. No PHP, no controller, no database.
2. **Variants.** They describe each state of the mockup (default, large, disabled, empty, long text...) as a variant in `storia/components`. The catalog now documents the component and can be reviewed by the designer.
3. **Real data.** When the component needs structured data, they write it once in `storia/data`, with `!data`, `!repeat` and `!lorem`.
4. **Behavior in PHP, only when needed.** When the component needs logic, the developer replaces the anonymous template with a Twig Component class of the same name. The template and the variants do not change: they are the specification.
5. **A safety net.** `storia:test` renders every variant and compares it with its snapshot. Run it in CI: a change of a component that alters another one shows up in the diff of the snapshots.

## Why it works

- The integrator never leaves Twig and YAML, but works with the real templates of the application, so what they validate is what ships.
- The developer gets a catalog of every state of every component, without writing stories.
- The snapshots are plain HTML files in `tests/storia/snapshots`, reviewed in the same pull request as the change.

## In practice

| Who         | Writes                                              | Checks                                  |
|-------------|-----------------------------------------------------|-----------------------------------------|
| Integrator  | `templates/components/*.html.twig`, `storia/**/*.yaml` | The preview, with `storia:watch`        |
| Developer   | Component classes, data providers, PHPUnit tests    | `storia:test` locally and in CI         |
| Designer    | Nothing                                             | The [static export](/docs/export/) published on each merge request |
