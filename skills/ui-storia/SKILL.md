---
name: ui-storia
description: Work with UI Storia (iq2i/storia-bundle), the design-system workbench built into Symfony and Twig. Use it whenever the project has a `storia/` folder, an `iq2i_storia` configuration or the iq2i/storia-bundle package, or when the user talks about Storia, stories, component variants, previewing or documenting a Twig template, Twig Component, form type or page in isolation, or checking how a component renders in the browser, even if Storia is not named.
---

# UI Storia

UI Storia lists the Twig templates, Twig Components, form types and pages of a Symfony project and renders each of them in isolation, with variants described in YAML. It renders the real templates with the real Twig of the application: filters, functions, services and asset helpers behave as in the app. Each variant is also a test: `storia:test` renders all of them and compares the HTML with snapshots.

The complete list of YAML keys, tags and options is in [references/yaml-reference.md](references/yaml-reference.md). Read it before writing anything beyond a simple variant.

Features marked *(1.0)* exist from version 1.0. Check the installed version with `composer show iq2i/storia-bundle` and fall back to the older behavior when it is lower.

## 1. Check the installation first

Verify instead of assuming:

- `config/packages/iq2i_storia.yaml`: `default_path` (default `%kernel.project_dir%/storia`), `enabled`, `watch`, `export`, `test`.
- The bundle is enabled when `kernel.debug` is true *(1.0)*. Before 1.0 it is always enabled unless `enabled: false` is set. When the routes are missing from `bin/console debug:router | grep storia`, the bundle is disabled in this environment.
- The route prefix is defined in `config/routes/*.yaml`, `/storia` by convention.
- `templates/bundles/IQ2iStoriaBundle/iframe.html.twig`: without it the previews have **no CSS and no JavaScript**. This is the first cause of "the component looks broken". See section 6.
- Open 2 or 3 files of `storia/components/` and copy the conventions of the project: naming, folders, data files, factories, shared files.

Run `bin/console` where the project runs. In Docker, use the container, the `Makefile` or the scripts of the project, not the host.

## 2. Read the catalog

Prefer these commands to reading YAML files one by one *(1.0)*:

```bash
bin/console storia:list                           # readable summary
bin/console storia:list --format=json             # type, source, YAML file, variants, resolved args, errors
bin/console storia:list --filter=components/button
bin/console storia:list --filter=components/button:large
bin/console storia:render components/button plain                  # the rendered HTML
bin/console storia:render components/button plain --format=twig    # the template source
bin/console storia:render components/button plain --format=include # code to use the component (components only)
```

`storia:render` is the quickest check that a variant renders: no browser, no server, errors on stderr and exit code 1. A view that cannot be loaded appears in `storia:list` with its error.

## 3. Layout of the `storia/` folder

```
storia/
├── _shared.yaml              # shared YAML anchors (a leading _ means: not a view)
├── data/                     # named data used with !data, not views (1.0)
│   └── menu.yaml
├── components/               # unit components
│   ├── badge.yaml
│   └── button/
│       ├── button.yaml
│       ├── button.md         # Notes tab of the view
│       └── button.html.twig  # optional local template (when there is no template key)
└── pages/                    # whole pages
    └── homepage/
        ├── homepage.yaml
        └── homepage.html.twig
```

Only `components/` and `pages/` feed the menu. The path of a view is its path relative to `storia/` without extension: `storia/components/button/button.yaml` is `components/button/button`. A Markdown file with the same name as the YAML file is rendered in the **Notes** tab.

## 4. Write a view

**Choosing the source for a project component**

- **Default to `component:`** with a few meaningful variants, usually 2 to 5. Each one must show a really different case: the default state, the main visual variants, edge cases such as long text, empty content or disabled. Do not generate one variant per combination of props: the tabs become unreadable and teach nothing more.
- **Switch to a local Twig template only for a complex component**, with many props or combinations to compare at a glance (for example size × color × state of a button). Put the template next to the YAML (`storia/components/button/button.html.twig`, no `template` key). It calls the component with `<twig:Button …>` in loops or a grid, to show everything on one board. Keep at least one variant in the YAML (`all: { args: {} }`), otherwise the view has nothing to render. Lists of combinations can be passed as `args`.
- **Reserve `template:`** for classic Twig templates that are not Twig Components (partials, layout macros).

A file uses **only one** of `template`, `component` or `form`.

```yaml
# Twig Component: the default choice (symfony/ux-twig-component required)
component: Button
variants:
    primary:
        args:
            variant: primary
        blocks:
            content: Send        # default block of the component
            icon: '<svg>…</svg>' # named block
    long_label:
        name: A very long label   # label of the tab, optional (1.0)
        blocks:
            content: !lorem 12
```

```yaml
# Classic template: args become the variables of the template
template: ui/progress.html.twig
variants:
    default:
        args:
            height: 2.5
```

Without a `template` key, Storia looks for `<same-name>.html.twig` next to the YAML file.

**Watch out for how Storia calls a component** (it writes a `<twig:Button …>` tag):

- Scalar args are written as attributes `name="value"`: a boolean or a number arrives as a **string** (`true` becomes `"1"`, `false` becomes an empty string). When a prop is strictly typed, use a data provider or check the rendering.
- Array args are passed as `:name="{json}"` with double quotes replaced by single quotes: avoid apostrophes in the strings of these arrays, or use a data provider.
- An arg that the component class exposes with `#[ExposeInTemplate]` is computed and is not passed.
- Components in sub-folders are written with `/` or `:` (`Folder/ButtonGroup`).

**Form types**: `form: App\Form\MyType` with optional `options` and `form_theme`. Three variants exist automatically: `default`, `disabled` and `error`. Since 1.0 you can add your own variants with `options` and `with_errors`, see the reference. Before 1.0, do not declare `variants` for a form.

**Realistic data** *(1.0)*: do not copy big arrays into every variant. Store them once in `storia/data/` and use `!data`; use `!repeat` to build lists and `!lorem` for filler text. Use a PHP data provider (`!data App\Storia\ProductFactory::createDefault`) for objects (product, order, user) and values that need logic. Before 1.0, a bare `Class::method` string is called; since 1.0 it is deprecated, write `!data Class::method`.

**Avoid duplication**: `include: _shared` shares YAML anchors, `extends: components/badge` inherits a view and overrides a part of it (deep merge). See the reference.

**Document the component**: add a `.md` file next to the YAML when the component has usage rules that are not obvious (props, blocks, accessibility). It becomes the Notes tab.

## 5. Check a rendering

1. Start with `bin/console storia:render <view> <variant>`: it shows the HTML or the exception, with no server.
2. For the look, the CSS or the JavaScript, open the **iframe route** directly in a browser, not the Storia interface:

```
<base-url>/storia/iframe/<view-path>?variant=<variant-key>
```

```
https://my-project.local/storia/iframe/components/button/button?variant=primary
https://my-project.local/storia/iframe/components/form/text?variant=error
```

Why the iframe route is better than `/storia/<view>`:

- The page contains **only** the component plus the CSS and JS declared in `iframe.html.twig`: clean screenshots, simple selectors, no frame to cross with Playwright or a browser tool.
- Twig errors appear as a full page with the Symfony stack trace instead of being lost in the iframe.
- The browser viewport is the viewport of the component: resize the window to test the responsive behavior.
- The profiler is disabled on this route: no debug toolbar in the rendering.

**`?variant=` is mandatory on the iframe route.** The main route redirects to the first variant, the iframe route does not: without `variant`, or with a name that does not exist, the page is **blank** and there is no error. When you get a blank page, check this parameter and the exact key of the variant in the YAML (the key, not the `name`).

A quick `curl -sk "<iframe-url>"` tells whether the HTML is generated. Use a real browser when the visual rendering, the CSS or the JS (Stimulus, Vue…) matter.

The **Controls** tab of the interface edits the args of the current variant and shows the result at once. It is built from the `{% props %}` of the template, the PHP class of the component or the `{% types %}` of a page. It is a tool for people: the iframe route takes the same changes as an `overrides` JSON parameter, which you rarely need.

Find the base URL in the configuration of the project (`.env*`, `docker-compose.yml`, `symfony server:list`, README) instead of guessing it. If you cannot find it, ask.

When a page template already contains `</body>` (it extends the base layout), Storia returns its HTML as is, without wrapping it in `iframe.html.twig`: the assets are then those of the layout of the project.

## 6. Load the assets of the project

When the previews have no style, create the override (the `!` extends only the blocks you need):

```twig
{# templates/bundles/IQ2iStoriaBundle/iframe.html.twig #}
{% extends '@!IQ2iStoria/iframe.html.twig' %}

{% block stylesheets %}{{ encore_entry_link_tags('app') }}{% endblock %}
{% block javascripts %}{{ encore_entry_script_tags('app') }}{% endblock %}
```

Use the Encore entry that the templates really use (read `webpack.config.js` and the base layout; a Sylius project often has separate shop and admin entries). With AssetMapper: `{% block javascripts %}{% block importmap %}{{ importmap('app') }}{% endblock %}{% endblock %}`. With Vite, use the tags of the Vite bundle. Make sure the assets are built (`npm run dev` or `watch`) before judging a rendering.

## 7. Test and publish

```bash
bin/console storia:test                   # render every variant, compare with the snapshots (1.0)
bin/console storia:test --filter=alert
bin/console storia:test --update          # accept the new HTML after a deliberate change
bin/console storia:export [output-dir]    # static website of the catalog (1.0)
```

A failure is a rendering error (Twig or PHP, with the name of the variant) or a snapshot difference, shown as a diff when `sebastian/diff` is installed. Snapshots are plain HTML files in `tests/storia/snapshots`: commit them with the change that causes them. Never run `--update` to silence a difference you did not expect: read the diff first. A snapshot that depends on the date, a random value or a CSRF token is never stable: make the data deterministic or disable the token in the test environment.

## 8. Live reload

`bin/console storia:watch` reloads automatically: a change in a YAML file of `storia/` reloads the whole page, a change in `templates/` or `assets/` (configurable with `iq2i_storia.watch`) reloads the iframe only. It is a blocking process: run it in the background or let the user start it, and do not block your session on it.

## 9. Checklist when you add or change a view

1. The YAML is in `storia/components/` or `storia/pages/` and uses a single source.
2. A component has a few meaningful variants, not one per combination. A form view has no `args` or `blocks`.
3. Shared data lives in `storia/data/`, not copied in every variant.
4. A `.md` file documents the non-obvious rules of use.
5. `bin/console storia:render <view> <variant>` works for each variant, with no exception.
6. When the visual rendering matters, check it in a browser on the iframe URL, with `?variant=`.
7. `bin/console storia:test` passes and the new snapshots are reviewed.

## Limits

- The commands render HTML. They do not take screenshots and do not check accessibility or the CSS.
- `storia:list` summarizes arguments that resolve to PHP objects (for example `object(App\Entity\User)`).
- Storia is for development. It is enabled with `kernel.debug` only *(1.0)*: do not expose it in production.
