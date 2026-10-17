# UI Storia YAML reference

## Contents
- Top-level keys
- Variants
- Form types
- Data: `!data`, `!repeat`, `!lorem`
- `include:` (shared anchors)
- `extends:` (inheritance)
- Bundle configuration, routes and commands
- Board of combinations for a complex component

## Top-level keys

| Key | Role | Constraints |
|---|---|---|
| `template` | Path of a Twig template of the project (`ui/badge.html.twig`) | Exclusive with `component` and `form` |
| `component` | Name of a Twig Component (`Button`, `Folder/ButtonGroup`) | Requires `symfony/ux-twig-component` |
| `form` | Class of a form type | Requires `symfony/form` |
| `variants` | Map `key → { name?, args?, blocks? }`, or `{ options?, with_errors? }` for a form | |
| `options` | Options of the form type | Form views only |
| `form_theme` | Twig template used as form theme | Form views only |
| `include` | File or list of files holding shared anchors | |
| `extends` | Parent view to inherit from | Path relative to `storia/`, no extension |

Without `template`, `component` or `form`, a `<name>.html.twig` next to the YAML file is used.

## Variants

```yaml
variants:
    outOfStock:          # the key is the value of ?variant=
        name: Sold out   # label of the tab (otherwise derived from the key: "Out of stock")
        args:            # Twig variables / props of the component
            inStock: false
        blocks:          # Twig Components only
            content: '…' # default block
            footer: '…'  # named block
```

The order of the variants in the YAML is the order of the tabs. The first one is displayed by default. Keys are kept as written, with hyphens turned into underscores. The `name` key is the label of the tab only since 1.0: before, it was ignored.

## Form types

```yaml
form: Symfony\Component\Form\Extension\Core\Type\ChoiceType
form_theme: 'form/custom_theme.html.twig'
options:
    choices:
        'In stock': true
        'Sold out': false
```

Three variants exist automatically: `default`, `disabled` (option `disabled: true`) and `error` (the error "Error message" is added and the form is submitted empty).

**Custom variants (1.0)** are added after the three automatic ones. A variant with the same name replaces the automatic one. A variant of a form takes `options` and `with_errors`, never `args` or `blocks`:

```yaml
variants:
    preselected:
        options:
            data: true
    unavailable:
        options:
            data: false
            disabled: true
```

The options of a variant win over the global `options` of the file (1.0). Before 1.0 the global options were merged last: do not put `disabled` in the global `options`, or the `disabled` variant becomes useless.

How the field is rendered, useful to read the result:

- The field is created with `createNamed('field', …)`: it is named `field` in the HTML (`name="field"`, `id="field"`).
- The default options are `label: Label` and `help: Help text`. Override them in `options` for a meaningful demo.
- The rendering is `form_start` / `form_row(form)` / `form_end`: the `form_row` of the theme is what is tested (label, widget, help, errors). For a compound type (`AddressType`, `RepeatedType`…), the whole sub-form is rendered.

### Show a form theme on every field type

One file per field type, inheriting from a common parent that holds the theme:

```yaml
# storia/_form.yaml: at the root, NOT in components/ (otherwise it is listed in the menu and fails)
form_theme: 'form/shop_theme.html.twig'
```

```yaml
# storia/components/form/text.yaml
extends: _form
form: Symfony\Component\Form\Extension\Core\Type\TextType
options:
    label: Name
    help: As it will appear on the invoice
    attr: { placeholder: 'Jane Doe' }
```

```yaml
# storia/components/form/choice_radio.yaml
extends: _form
form: Symfony\Component\Form\Extension\Core\Type\ChoiceType
options:
    label: Delivery
    expanded: true
    choices: { Standard: standard, Express: express }
```

Types to cover: `TextType`, `EmailType`, `PasswordType`, `TelType`, `NumberType` or `IntegerType`, `TextareaType`, `ChoiceType` (select, `expanded` for radios, `expanded` + `multiple` for checkboxes, `multiple` alone for a multiple select), `CheckboxType`, `DateType` (`widget: single_text`), `FileType`, and the business types of the project (for example Sylius: `AddressType`, `CountryCodeChoiceType`) when the theme customizes them. Name the files after the way they render (`choice_radio`, `choice_checkboxes`) rather than after the class alone, as the same type gives different renderings.

When the theme is already declared in `config/packages/twig.yaml` (`form_themes`), it applies everywhere: the `_form.yaml` parent then only shares common `options`, or can be omitted. `form_theme` is for testing a theme that is **not global**, or an alternative theme (admin versus shop).

## Data (1.0)

Named data lives in `storia/data/`. It is not a view and does not appear in the menu. Tags are resolved in the `args` and `blocks` of a variant and in the `options` of a form.

```yaml
# storia/data/menu.yaml
items:
    - { label: Home, url: / }
    - { label: Shop, url: /shop }
```

```yaml
# storia/components/header.yaml
component: Header
variants:
    default:
        args:
            menu: !data menu
```

| Tag | Use |
|---|---|
| `!data menu` | Value of `storia/data/menu.yaml`. Sub-folders are part of the name (`!data shop/catalog`). A dotted path reaches inside (`!data menu.items.0`, `!data shop/product.default.price`). |
| `!data { ref: product.default, with: { price: 999 } }` | The same, with values replaced. Mappings are merged, lists are replaced. |
| `!data App\Storia\ProductFactory::createDefault` | The value returned by a PHP method. Static, or called on the service when the class is one. |
| `!repeat` | `{ count: 4, item: { name: 'Product {i}' } }` builds a list. `{i}` is the position, from 1, in every string of the item. |
| `!lorem 12` | Deterministic filler text: `!lorem 12` (words), `!lorem 2 sentences`, `!lorem 1 paragraph`. Inside `!repeat`, each item gets another text. |

Before 1.0 there are no tags: a string `Namespace\Class::method`, even nested in an array, is replaced by what the method returns. In 1.0 this still works but is deprecated: write `!data Class::method`. Prefer dedicated factories (in a namespace such as `App\Storia\`) that return deterministic data, so the renderings do not change between two loads, and snapshots stay stable.

## `include:` shared anchors

```yaml
# storia/_shared.yaml
_definitions:
    badge_base: &badge_base
        class: text-xs font-medium px-2.5 py-0.5 rounded
```

```yaml
# storia/components/badge.yaml
include: _shared        # or [_colors, _sizes]
template: ui/badge.html.twig
variants:
    default:
        args:
            <<: *badge_base
```

`_definitions` is removed before processing. Prefix shared files with `_`. To share data between views, prefer `!data`.

## `extends:` inheritance

```yaml
# storia/components/badge-red.yaml
extends: components/badge
variants:
    default:
        args:
            class: bg-red-100 text-red-800
```

Deep merge: one arg of a variant can be overridden. Inheritance can be chained, and a circular reference raises an explicit error.

## Bundle configuration, routes and commands

```yaml
# config/packages/iq2i_storia.yaml
iq2i_storia:
    default_path: '%kernel.project_dir%/storia'
    enabled: ~                  # 1.0: kernel.debug by default; set true or false to force it
    watch:
        - '%kernel.project_dir%/templates'
        - '%kernel.project_dir%/assets'
    export:
        output_dir: '%kernel.project_dir%/var/storia/export'
        public_dir: '%kernel.project_dir%/public'
        assets: []
    test:
        snapshot_path: '%kernel.project_dir%/tests/storia/snapshots'
```

Routes (the prefix is defined in `config/routes/`, `/storia` by convention):

- `iq2i_storia_view`: `/storia/{view}?variant=…`, the full interface.
- `iq2i_storia_iframe`: `/storia/iframe/{view}?variant=…`, the rendering alone (use it to check a rendering).
- `iq2i_storia_changes`: `/storia/changes`, the live reload stream.

Commands: `storia:list`, `storia:render`, `storia:test`, `storia:export`, `storia:watch`. `storia:watch` runs a native binary shipped with the bundle (macOS, Linux, Windows). In Docker, start it inside the PHP container.

## Board of combinations for a complex component

Use it only when a few variants are not enough, with many props or combinations.

```yaml
# storia/components/button/button_matrix.yaml (no template key: the neighbor .html.twig is used)
variants:
    all:
        args:
            sizes: [sm, md, lg]
            colors: [primary, secondary, danger]
```

```twig
{# storia/components/button/button_matrix.html.twig #}
<table>
    {% for color in colors %}
        <tr>
            {% for size in sizes %}
                <td><twig:Button size="{{ size }}" color="{{ color }}">{{ color }} {{ size }}</twig:Button></td>
                <td><twig:Button size="{{ size }}" color="{{ color }}" :disabled="true">disabled</twig:Button></td>
            {% endfor %}
        </tr>
    {% endfor %}
</table>
```

Keep this board next to a `button.yaml` view in `component:` with the 2 to 5 usual variants. The board is for comparing, the `component:` view is for documenting the usage.
