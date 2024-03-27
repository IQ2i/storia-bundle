# UI Storia

## Overview

UI Storia is a powerful tool designed to simplify and speed up the development process of user interfaces by providing a visual and modular development environment for interface in your Symfony application.

![UI Storia, a powerful tool designed to simplify and speed up the development process of user interfaces by providing a visual and modular development environment for interface in your Symfony application.](/doc/images/ui-storia-promo.png)

Unlike Fractal or StoryBook, which rely on a port of Twig to JS for Symfony projects, UI Storia allows direct work on project templates, thus enabling the safe use of the latest Twig features available.

## Sponsors

<p align="center">
  <a target="_blank" href="https://www.mezcalito.fr">
    <img alt="Mezcalito - Agence Digitale à Grenoble depuis 2006" src="https://raw.githubusercontent.com/IQ2i/storia-bundle/main/doc/images/mezcalito.png" width="300">
  </a>
</p>

## Installation

To install UI Storia into your Symfony project, follow these steps:

1. Make sure you have Symfony installed locally.
2. Add UI Storia to your project using Composer:
    ```bash
    composer require iq2i/storia-bundle
    ```
3. Once installation is complete, activate the bundle in the `config/bundles.php` file:
    ```php
    return [
        // ...
        IQ2i\StoriaBundle\IQ2iStoriaBundle::class => ['all' => true],
    ];
    ```
4. Create a new routing file to add UI Storia's routes:
    ```yaml
    # config/routes/is2i_storia.yaml
    iq2i_storia:
        resource: '@IQ2iStoriaBundle/config/routes.php'
        prefix: '/storia'
    ```
5. Create the `storia` folder at the root of your project, with two subfolders: `components` and `pages`.
6. Configure UI Storia following the detailed configuration steps below.

## Configuration

By default, UI Storia will try to read YAML files in the `storia` folder at the root of your application.
You can change this behavior by creating a configuration file `config/packages/iq2i_storia.yaml` with the following content:

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    default_path: '%kernel.project_dir%/new-folder'
```

It is also possible, from this same file, to enable or disable the bundle (specifically to condition its routes) with a configuration parameter:

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    # use an environment variable for easier configuration
    enabled: '%env(IQ2I_STORIA_ENABLED)%'
```

UI Storia will render your interfaces without CSS or JavaScript.  
To instruct UI Storia to use your styles and scripts, simply override the `iframe.html.twig` template following the [Symfony documentation](https://symfony.com/doc/current/bundles/override.html#templates):


```twig
{# templates/bundles/Iq2iStoriaBundle/iframe.html.twig #}

{% extends '@!IQ2iStoria/iframe.html.twig' %}

{# if you use WebpackEncoreBundle #}
{% block stylesheets %}
    {{ encore_entry_link_tags('app') }}
{% endblock %}
{% block javascripts %}
   {{ encore_entry_script_tags('app') }}
{% endblock %}

{# if you use AssetMapper #}
{% block javascripts %}
   {% block importmap %}{{ importmap('app') }}{% endblock %}
{% endblock %}
```

> [!TIP]
> Note the use of `!` in the extended template name to override only the blocks you need rather than the entire template.

## Interfaces

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

### Template / Component

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

### Variants

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

> [!IMPORTANT]
> Note here that the `content` block is the default block for Twig Component according to the documentation ([see here](https://symfony.com/bundles/ux-twig-component/current/index.html#passing-html-to-components)), so it will have a different behavior that we will detail later.
