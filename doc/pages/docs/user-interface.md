---
title: The interface
layout: docs
menu:
  docs:
    weight: 55
---

Open the route you registered (`/storia` by default) to get the catalog of your interfaces.

![The UI Storia interface: sidebar, variants, preview and code tabs](/media/ui-overview.webp)

## Sidebar

The sidebar lists the views found in the `storia/components` and `storia/pages` folders. Sub-folders become collapsible groups. Labels are deduced from the file names: `buttonGroup.yaml` is displayed as "Button group".

Drag the separator between the sidebar and the content to resize it.

## Variants

The tabs on top of the preview are the variants of the current view. The selected variant is part of the URL (`?variant=large`), so you can share a link to it.

## Preview actions

The actions on the right of the variants act on the preview:

| Action     | What it does                                                                          |
|------------|---------------------------------------------------------------------------------------|
| Refresh    | Reloads the preview                                                                   |
| Zoom in    | Enlarges the preview                                                                  |
| Zoom out   | Shrinks the preview                                                                   |
| Zoom 100%  | Resets the zoom                                                                       |
| Color scheme | Forces the light or dark scheme of the preview, see [Color scheme](#color-scheme)   |
| Iframe     | Opens the preview alone in a new tab, without the interface                           |
| Open in Figma | Opens the Figma design of the view in a new tab (views with a `figma` key)         |
| Figma      | Shows the Figma design next to the preview, to compare it with the rendering          |
| Copy       | Copies the address of the current page                                                |

The preview is rendered in an iframe, with the styles and scripts of your application: see [Load your styles and scripts](/docs/installation/#load-your-styles-and-scripts). Drag the vertical separator on its right to test your component at another width.

The Figma actions appear on the views declaring a [`figma`](/docs/interfaces/#figma) link. The design is embedded with [Figma embeds](https://www.figma.com/developers/embed) and follows the sharing settings of the file: a private file asks to sign in. It loads when the panel is opened for the first time, and the separator between the preview and the design can be dragged. The static export leaves Figma out.

![The Figma design of a button shown next to its Twig rendering](/media/figma-compare.webp)

### Color scheme

The color scheme button cycles between auto, light and dark. In auto, the preview follows the system setting. Light and dark are applied on the `<html>` element of the preview only, so you can check both schemes of a component without changing the setting of your computer:

```html
<html data-theme="dark" style="color-scheme: dark">
```

The `color-scheme` property switches the default colors of the browser, the form controls and the CSS `light-dark()` function. The `data-theme` attribute is the hook for your own styles. A `@media (prefers-color-scheme: dark)` media query cannot be forced from a page: if your styles rely on it, make them react to the attribute as well.

```css
:root {
    color-scheme: light dark;
    --surface: light-dark(#fff, #111);
}
```

```css
/* Tailwind 4 */
@custom-variant dark (&:where([data-theme="dark"], [data-theme="dark"] *));
```

```js
// Tailwind 3 (tailwind.config.js)
darkMode: ['selector', '[data-theme="dark"]'],
```

The choice is kept in the browser, across views and reloads. It is applied before the preview renders, and in the tab opened by the Iframe action. A view that renders its own full page, or a project that replaces the whole `iframe.html.twig` template instead of extending its blocks, receives the scheme once the preview is loaded.

<video autoplay loop muted playsinline controls preload="metadata" poster="/media/interface-poster.webp" aria-label="Zooming, resizing the preview, browsing the code tabs and switching variant in UI Storia">
    <source src="/media/interface.webm" type="video/webm">
    <source src="/media/interface.mp4" type="video/mp4">
</video>

## Code tabs

Under the preview, the tabs show what is rendered for the current variant. Each code block has a button to copy it.

| Tab      | Content                                                                                                |
|----------|--------------------------------------------------------------------------------------------------------|
| Controls | Edit the arguments of the variant and see the result, see [Controls](/docs/controls/)                  |
| Twig     | The Twig source of the template or component                                                           |
| HTML     | The HTML generated by the variant                                                                      |
| Include  | Code to paste in your templates to use the component with the arguments of the variant (components only) |
| Notes    | The Markdown file next to the YAML file, see [Notes](/docs/interfaces/#notes)                          |

![The Include tab, with the code to paste in a template](/media/ui-include-tab.webp)

The Controls, Include and Notes tabs only appear when the view has something to show in them. Drag the horizontal separator between the preview and the tabs to give more room to one or the other.

## Views without variants

A view that defines no variant is listed in the sidebar, but there is nothing to preview. Add at least one variant: `storia:test` reports a view without variants as an error in CI.
