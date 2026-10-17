---
title: Interfaces
layout: docs
menu:
  docs:
    weight: 50
---

UI Storia allows you to create interfaces for individual components or more complete pages.  
You can work with Twig templates from your Symfony application or with local templates that will be used only for UI Storia.

To describe your interfaces, simply create a YAML file in the `storia/components` or `storia/pages` folder as follows:

```yaml
# storia/components/progress.yaml

template: ui/progress.html.twig
variants:
    small:
        args:
            height: 1.5

    default:
        args:
            height: 2.5

    large:
        args:
            height: 4

    extra_large:
        args:
            height: 6
```

The difference between the `storia/components` folder and the `storia/pages` folder is as follows:  
By convention, use the `storia/components` folder for interfaces related to macro components and the `storia/pages` folder for interfaces for more complete pages.

Here's the configuration details:

* `template`: Corresponds to the path to the Twig template of your Symfony application that you want to work with.
* `variants`: This is the list of different variants you want to create.

## Template / Component

The `template` key is optional because you can also use a Twig template at the same level and with the same name as your YAML file.
For example, for your YAML file `storia/components/progress.yaml`, you can create a file `storia/components/progress.html.twig` and this file will be used by UI Storia.

UI Storia also provides native support for [Twig Component](https://symfony.com/bundles/ux-twig-component/current/index.html) for your interfaces. To do this, simply replace the `template` key with the `component` key as shown below:

```yaml
# storia/components/button.yaml

component: Button
variants:
    plain:
        args:
            class: plain
            label: Plain button

    outline:
        args:
            class: outline
            label: Outline button
```

## Form Types

UI Storia provides native support for [Symfony Form Types](https://symfony.com/doc/current/forms.html), allowing you to preview and test form fields in isolation.

To work with a Form Type, use the `form` key instead of `template` or `component`:

```yaml
# storia/components/form/text.yaml

form: Symfony\Component\Form\Extension\Core\Type\TextType
```

```yaml
# storia/components/form/choice.yaml

form: Symfony\Component\Form\Extension\Core\Type\ChoiceType
options:
    choices:
        'In Stock': true
        'Out of Stock': false
```

### Automatic Variants

When using the `form` key, UI Storia automatically generates **three variants** for you:

1. **default**: The form field in its normal state
2. **disabled**: The form field with `disabled: true` option
3. **error**: The form field with a validation error displayed

![A choice field in its error variant](/media/form-variants.webp)

### Custom Variants

Declare your own variants with the `variants` key. They are added after the three automatic ones, and a variant with the same name as an automatic one replaces it.

A variant of a form accepts two keys instead of `args` and `blocks`:

* `options`: Form options merged over the global `options` of the file. The variant wins.
* `with_errors`: Set it to `true` to display a validation error.

```yaml
# storia/components/form/choice.yaml

form: Symfony\Component\Form\Extension\Core\Type\ChoiceType
options:
    choices:
        'In Stock': true
        'Out of Stock': false
variants:
    preselected:
        options:
            data: true
    unavailable:
        options:
            data: false
            disabled: true
```

### Options

The `options` key allows you to pass options to your Form Type constructor, just like you would when creating a form in Symfony:

```yaml
# storia/components/form/email.yaml

form: Symfony\Component\Form\Extension\Core\Type\EmailType
options:
    required: false
    attr:
        placeholder: 'Enter your email'
```

### Form Theme

You can also specify a custom form theme for rendering your form field using the `form_theme` key:

```yaml
# storia/components/form/custom.yaml

form: App\Form\CustomType
form_theme: 'forms/custom_theme.html.twig'
options:
    label: 'Custom Field'
```

:::important markdown-alert markdown-alert-important
<p class="markdown-alert-title">
    <svg class="octicon octicon-report mr-2" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path d="M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v9.5A1.75 1.75 0 0 1 14.25 13H8.06l-2.573 2.573A1.458 1.458 0 0 1 3 14.543V13H1.75A1.75 1.75 0 0 1 0 11.25Zm1.75-.25a.25.25 0 0 0-.25.25v9.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h6.5a.25.25 0 0 0 .25-.25v-9.5a.25.25 0 0 0-.25-.25Zm7 2.25v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path></svg>
    Important
</p>

The `form` key cannot be used together with `template` or `component`. Choose one approach per YAML file.

The `options` and `form_theme` keys are only allowed when using the `form` key - they cannot be used with templates or components.

The `options` and `with_errors` keys of a variant are allowed for forms only, as `args` and `blocks` are allowed for templates and components only.
:::

## Variants

The `variants` key lists the different variations of your interface, passing different arguments to your template.

Each variant is described with a name (which can be overridden as shown in the example below) and an array of arguments called `args`.
Twig Component support also brings a `blocks` key to configure [HTML blocks](https://symfony.com/bundles/ux-twig-component/current/index.html#passing-html-to-components-via-blocks) that will be passed to your component.

```yaml
# storia/components/button.yaml

component: Button
variants:
    plain:
        args:
            class: plain
        blocks:
            content: Plain button
            svg: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="200" height="200" viewBox="0 0 42 42"><path d="M42 20H22V0h-2v20H0v2h20v20h2V22h20z"/></svg>'

    outline:
        name: My outline button 
        args:
            class: outline
        blocks:
            content: Outline button
            svg: '<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="200" height="200" viewBox="0 0 42 42"><path d="M42 20H22V0h-2v20H0v2h20v20h2V22h20z"/></svg>'
```

:::important markdown-alert markdown-alert-important
<p class="markdown-alert-title">
    <svg class="octicon octicon-report mr-2" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path d="M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v9.5A1.75 1.75 0 0 1 14.25 13H8.06l-2.573 2.573A1.458 1.458 0 0 1 3 14.543V13H1.75A1.75 1.75 0 0 1 0 11.25Zm1.75-.25a.25.25 0 0 0-.25.25v9.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h6.5a.25.25 0 0 0 .25-.25v-9.5a.25.25 0 0 0-.25-.25Zm7 2.25v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path></svg>
    Important
</p>

Note here that the `content` block is the default block for Twig Component according to the documentation ([see here](https://symfony.com/bundles/ux-twig-component/current/index.html#passing-html-to-components)), so it will have a different behavior that we will detail later.
:::


## Advanced: include and extends

To share data between views, prefer [named data](/docs/data/). `include:` and `extends:` are advanced features meant for sharing YAML structure.

UI Storia supports two directives to avoid duplication across your YAML files: `include:` for sharing anchor definitions, and `extends:` for inheriting and overriding a complete configuration.

### `include:` — Shared anchor definitions

The `include:` directive lets you define YAML anchors in a shared file and reuse them across multiple configuration files.

Create a shared file anywhere in your `storia/` folder (conventionally prefixed with `_` to indicate it is not a standalone view):

```yaml
# storia/_shared.yaml

_definitions:
    default_badge: &default_badge
        class: bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded

    large_badge: &large_badge
        class: bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded
```

Then include it from any configuration file using `include:` and reference the anchors with the standard YAML `*` syntax:

```yaml
# storia/components/badge.yaml

include: _shared

template: ui/badge.html.twig
variants:
    default:
        args:
            <<: *default_badge

    large:
        args:
            <<: *large_badge
```

You can include multiple files using a list:

```yaml
include: [_colors, _sizes]

# or equivalently:
include:
    - _colors
    - _sizes
```

:::tip markdown-alert markdown-alert-tip
<p class="markdown-alert-title">
    <svg class="octicon octicon-light-bulb mr-2" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path d="M8 1.5c-2.363 0-4 1.69-4 3.75 0 .984.424 1.625.984 2.304l.214.253c.223.264.47.556.673.848.284.411.537.896.621 1.49a.75.75 0 0 1-1.484.211c-.04-.282-.163-.547-.37-.847a8.456 8.456 0 0 0-.542-.68c-.084-.1-.173-.205-.268-.32C3.201 7.75 2.5 6.766 2.5 5.25 2.5 2.31 4.863 0 8 0s5.5 2.31 5.5 5.25c0 1.516-.701 2.5-1.328 3.259-.095.115-.184.22-.268.319-.207.245-.383.453-.541.681-.208.3-.33.565-.37.847a.751.751 0 0 1-1.485-.212c.084-.593.337-1.078.621-1.489.203-.292.45-.584.673-.848.075-.088.147-.173.213-.253.561-.679.985-1.32.985-2.304 0-2.06-1.637-3.75-4-3.75ZM5.75 12h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM6 15.25a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Z"></path></svg>
    Tip
</p>

The `_definitions:` key is reserved for anchor definitions and is automatically stripped before the configuration is processed. It will never appear as a variant or option in the UI.
:::

### `extends:` — Configuration inheritance

The `extends:` directive allows a configuration file to inherit all settings from a parent file and selectively override only the parts that differ.

```yaml
# storia/components/badge.yaml

template: ui/badge.html.twig
variants:
    default:
        args:
            class: bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded
    large:
        args:
            class: bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded
    bordered:
        args:
            class: bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded border border-blue-400
```

```yaml
# storia/components/badge-red.yaml

extends: components/badge

variants:
    default:
        args:
            class: bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded
    large:
        args:
            class: bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded
```

In this example, `badge-red.yaml` inherits the `template` key and the `bordered` variant from the parent, and only overrides `default` and `large`.

The merge is **deep**: nested keys (like `args` within a variant) are merged individually, so you can override a single argument without redefining all the others.

Extensions can be chained — a file that uses `extends:` can itself be extended by another file.

:::important markdown-alert markdown-alert-important
<p class="markdown-alert-title">
    <svg class="octicon octicon-report mr-2" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path d="M0 1.75C0 .784.784 0 1.75 0h12.5C15.216 0 16 .784 16 1.75v9.5A1.75 1.75 0 0 1 14.25 13H8.06l-2.573 2.573A1.458 1.458 0 0 1 3 14.543V13H1.75A1.75 1.75 0 0 1 0 11.25Zm1.75-.25a.25.25 0 0 0-.25.25v9.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h6.5a.25.25 0 0 0 .25-.25v-9.5a.25.25 0 0 0-.25-.25Zm7 2.25v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 9a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path></svg>
    Important
</p>

Circular references (file A extending file B which extends file A) are detected at runtime and will throw an explicit error.
:::

## Notes

A Markdown file next to the YAML file, with the same name, becomes the **Notes** tab of the view. Use it to document the component: when to use it, accessibility rules, links to the design.

```
storia/components/button/button.yaml
storia/components/button/button.md
```

The file is rendered as HTML, and the tab only appears when the file exists.

![The Notes tab rendering the Markdown file of the Card component](/media/notes-tab.webp)
