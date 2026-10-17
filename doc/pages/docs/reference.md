---
title: Reference
layout: docs
menu:
  docs:
    weight: 120
---

Everything on this page is **stable**: it only changes in a major version, see the [compatibility policy](/docs/upgrade/#compatibility-policy). A test compares the schema of the view files with a reference file, so a change cannot happen by accident.

## View files

A view file is a YAML file in `storia/components` or `storia/pages`. The path of the file without its extension is the path of the view.

| Key | Type | Description |
|-----|------|-------------|
| `template` | string | Twig template to render. Optional when a Twig file with the same name sits next to the YAML file. |
| `component` | string | Name of a Twig Component. Needs `symfony/ux-twig-component`. |
| `form` | string | Class of a Symfony form type. Needs `symfony/form`. |
| `variants` | map | The variations of the view, indexed by name. |
| `options` | map | Options of the form type. Form views only. |
| `form_theme` | string | Twig template used as form theme. Form views only. |
| `include` | string or list | Files whose `_definitions` anchors are available in this file. |
| `extends` | string | View file whose content is merged under this one. |
| `_definitions` | any | Anchors to share with `include`. Removed from the final configuration. |

`template`, `component` and `form` cannot be used together.

### Variants

| Key | Type | Views | Description |
|-----|------|-------|-------------|
| `name` | string | all | Label displayed instead of the name deduced from the key. |
| `args` | map | template, component | Arguments passed to the template or props of the component. |
| `blocks` | map | component | Content of the blocks of the component. |
| `options` | map | form | Form options for this variant, merged over the `options` of the view. |
| `with_errors` | bool | form | Displays a validation error. |

A form view always has the variants `default`, `disabled` and `error`. A variant with the same name replaces the automatic one.

### Tags

Available in `args`, `blocks` and form `options`. See [Data](/docs/data/).

| Tag | Description |
|-----|-------------|
| `!data name` | Value of a data file, a dotted path inside it, or the result of `Class::method`. |
| `!data { ref: name, with: { ... } }` | The same, with some values replaced. |
| `!repeat { count: n, item: ... }` | A list of `n` items, `{i}` is the position of the item. |
| `!lorem 12`, `!lorem 2 sentences`, `!lorem 1 paragraph` | Deterministic placeholder text. |

### Data files

Files of `storia/data` are not views. They hold any YAML value, referenced by their path without extension.

## Bundle configuration

```yaml
iq2i_storia:
    default_path: '%kernel.project_dir%/storia'
    enabled: ~          # default: kernel.debug
    watch:              # paths watched for iframe reloads
        - '%kernel.project_dir%/assets'
        - '%kernel.project_dir%/templates'
    export:
        output_dir: '%kernel.project_dir%/var/storia/export'
        public_dir: '%kernel.project_dir%/public'
        assets: []
    test:
        snapshot_path: '%kernel.project_dir%/tests/storia/snapshots'
```

## Commands

| Command | Options | Description |
|---------|---------|-------------|
| `storia:watch` | | Reloads the preview when a file changes. |
| `storia:export [output-dir]` | | Exports the catalog as a static website. |
| `storia:test` | `--filter`, `--update`, `--no-snapshots`, `--snapshot-path` | Renders every variant and compares it with its snapshot. |
| `storia:list` | `--format=txt\|json`, `--filter` | Lists the views and their variants. |
| `storia:render view variant` | `--format=html\|twig\|include` | Prints what a variant renders. |

## PHPUnit

| Class | Description |
|-------|-------------|
| `IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants` | Data provider listing the variants of the catalog. |
| `IQ2i\StoriaBundle\Test\PHPUnit\StoriaTestCaseTrait` | Assertions to render a variant and compare it with its snapshot. |
