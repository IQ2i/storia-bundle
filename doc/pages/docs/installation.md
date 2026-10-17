---
title: Installation
layout: docs
menu:
  docs:
    weight: 40
---

## Require the package

Add [iq2i/storia-bundle](https://packagist.org/packages/iq2i/storia-bundle) to your composer.json file:

```bash
composer require iq2i/storia-bundle
```

## Register and configure the bundle

If you are using Symfony Flex, the following steps should be done automatically. Otherwise, follow the instructions.

### Register the bundle

Inside config/bundles.php, add the following line:

```php
// config/bundles.php

return [
    // ...
    IQ2i\StoriaBundle\IQ2iStoriaBundle::class => ['all' => true],
];
```

### Add routes

Create a new routing file to add UI Storia's routes:

```yaml
# config/routes/iq2i_storia.yaml
iq2i_storia:
    resource: '@IQ2iStoriaBundle/config/routes.php'
    prefix: '/storia'
```

### Create folders where YAML files are stored

Create the `storia` folder at the root of your project, with two subfolders: `components` and `pages`.

## Configure the bundle

By default, UI Storia will try to read YAML files in the `storia` folder at the root of your application.
You can change this behavior by creating a configuration file `config/packages/iq2i_storia.yaml` with the following content:

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    default_path: '%kernel.project_dir%/new-folder'
```

### When is the bundle enabled?

UI Storia is enabled when `kernel.debug` is true, like the Symfony profiler: in `dev` and `test`, not in `prod`. Its routes answer with a 404 when it is disabled. The commands (`storia:test`, `storia:export`, ...) keep working in every environment.

The content of the `storia/` folder is executed by the bundle, see [Security](/docs/data/#security).

Force the value with the `enabled` option when you need to:

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    # expose UI Storia on a staging environment that runs with debug disabled
    enabled: true
```

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    # or turn it off everywhere, with an environment variable for easier configuration
    enabled: '%env(bool:IQ2I_STORIA_ENABLED)%'
```

## Load your styles and scripts

UI Storia will render your interfaces without CSS or JavaScript.  
To instruct UI Storia to use your styles and scripts, simply override the `iframe.html.twig` template following the [Symfony documentation](https://symfony.com/doc/current/bundles/override.html#templates):

```twig
{# templates/bundles/IQ2iStoriaBundle/iframe.html.twig #}

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

:::tip markdown-alert markdown-alert-tip
<p class="markdown-alert-title">
    <svg class="octicon octicon-light-bulb mr-2" viewBox="0 0 16 16" version="1.1" width="16" height="16" aria-hidden="true"><path d="M8 1.5c-2.363 0-4 1.69-4 3.75 0 .984.424 1.625.984 2.304l.214.253c.223.264.47.556.673.848.284.411.537.896.621 1.49a.75.75 0 0 1-1.484.211c-.04-.282-.163-.547-.37-.847a8.456 8.456 0 0 0-.542-.68c-.084-.1-.173-.205-.268-.32C3.201 7.75 2.5 6.766 2.5 5.25 2.5 2.31 4.863 0 8 0s5.5 2.31 5.5 5.25c0 1.516-.701 2.5-1.328 3.259-.095.115-.184.22-.268.319-.207.245-.383.453-.541.681-.208.3-.33.565-.37.847a.751.751 0 0 1-1.485-.212c.084-.593.337-1.078.621-1.489.203-.292.45-.584.673-.848.075-.088.147-.173.213-.253.561-.679.985-1.32.985-2.304 0-2.06-1.637-3.75-4-3.75ZM5.75 12h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1 0-1.5ZM6 15.25a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Z"></path></svg>
    Tip
</p>

Note the use of `!` in the extended template name to override only the blocks you need rather than the entire template.
:::
