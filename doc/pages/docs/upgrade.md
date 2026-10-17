---
title: Upgrade
layout: docs
menu:
  docs:
    weight: 130
---

## Supported versions

| UI Storia | PHP    | Symfony          |
|-----------|--------|------------------|
| 1.x       | >= 8.3 | 6.4, 7.x and 8.x |

The test suite runs in CI on the lowest and the highest supported versions of PHP and Symfony, and on the next Symfony version in development.

## Compatibility policy

UI Storia follows [Semantic Versioning](https://semver.org/). Within a major version, what is **stable** keeps working, and what is **internal** can change in any release.

### What is stable

- The YAML format of the view files and the data files, as listed in the [reference](/docs/reference/).
- The `iq2i_storia` configuration of the bundle.
- The commands and their arguments and options: `storia:watch`, `storia:export`, `storia:test`, `storia:list`, `storia:render`. The JSON printed by `storia:list --format=json` only gets new keys.
- The PHPUnit helpers `IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants` and `IQ2i\StoriaBundle\Test\PHPUnit\StoriaTestCaseTrait`.
- The templates and blocks you can override, such as `iframe.html.twig`.

### What is internal

Every other class of the bundle (builders, resolvers, exporter, renderer, controllers, DTOs) is marked `@internal`. Do not extend or call it: it can change in a minor or a patch release. The HTML of the UI Storia interface and its CSS classes are internal too.

### Deprecations and support

- A feature is deprecated in a minor version, with a deprecation notice that tells what to use instead, and removed in the next major version.
- Dropping the support of a PHP or Symfony version is done in a minor version, never in a patch version. A PHP version is supported until its end of life, a Symfony version until the end of its bug fixes.
- A new snapshot format would be a major change: the snapshots written by `storia:test` are plain HTML files, normalized in the same way for the whole major version.

## Upgrade from 0.5 to 0.6

### UI Storia is enabled with `kernel.debug` only

The bundle used to be always enabled. It is now enabled when `kernel.debug` is true, as the Symfony profiler. If you expose UI Storia on an environment that runs with debug disabled (a staging environment for example), force it:

```yaml
# config/packages/staging/iq2i_storia.yaml
iq2i_storia:
    enabled: true
```

Nothing changes in `dev` and `test`. The commands work in every environment.

### `iq2i_storia_menu()` is removed

The sidebar is built by the bundle. If you override `view.html.twig` and call this function, copy the sidebar markup from the new template instead. Most projects only override `iframe.html.twig`, which is not affected.

### Use `!data` to call PHP

A bare `Class::method` string in an argument is still resolved, with a deprecation notice. Replace it with the tag:

```yaml
# before
product: App\Factory\ProductFactory::createDefault

# after
product: !data App\Factory\ProductFactory::createDefault
```

### `symfony/form` and `symfony/ux-twig-component` are optional

Both are suggested packages. Install them to preview form types and Twig Components:

```bash
composer require symfony/form symfony/ux-twig-component
```

### Form variants

Form views accept custom variants with `options` and `with_errors`. The options of a variant now win over the global `options` of the file. If you relied on a global option overriding an automatic variant, for example `disabled: false` in `options`, move it to a declared variant.

### Snapshots

`storia:test` writes its snapshots in `tests/storia/snapshots`. Commit them, and set `iq2i_storia.test.snapshot_path` to use another folder. Run `storia:test` in your CI: a view without variants and a missing snapshot make the command fail there.

### Variant names

The `name` key of a variant is now used as the label of its tab. Remove it from your files if you do not want the label to change.

### Blocks are plain HTML

The `blocks` of a component are printed as is and are no longer compiled as Twig. Move the logic to an argument or to the component template:

```yaml
# before
blocks:
    content: '<strong>{{ "now"|date("Y") }}</strong>'

# after
blocks:
    content: '<strong>2026</strong>'
```
