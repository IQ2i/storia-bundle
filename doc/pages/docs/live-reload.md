---
title: Live reload
layout: docs
menu:
  docs:
    weight: 80
---

UI Storia comes with a live reload feature that automatically refreshes the preview when you modify your files.

## Requirements

`storia:watch` runs a small native binary shipped with the bundle in `tools/watcher/bin/`. It uses the notifications of the operating system, so it is fast and light. The binary is chosen from your system:

| System  | Architectures      |
|---------|--------------------|
| macOS   | arm64, amd64       |
| Linux   | arm64, amd64       |
| Windows | one binary         |

The command fails with an explicit error on any other operating system.

If your application runs in Docker, start the command inside the PHP container: the Linux binary is used there, and it watches the files as the container sees them.

```bash
docker compose exec php bin/console storia:watch
```

The live reload is only available in the application. A [static export](/docs/export/) has no live reload.

## Starting the watcher

Run the following command to start watching for file changes:

```bash
bin/console storia:watch
```

The watcher will display which directories it is monitoring and log every detected change:

```
Watching [page]: /path/to/project/storia
Watching [iframe]: /path/to/project/templates
Watching [iframe]: /path/to/project/assets
Watching for changes… (Ctrl+C to stop)
[14:23:07] Change detected: /path/to/project/storia/components/badge.yaml (write) → page reload
[14:23:41] Change detected: /path/to/project/templates/ui/badge.html.twig (write) → iframe reload
```

Press `Ctrl+C` to stop the watcher. The browser will automatically disconnect from the live reload stream.

## Reload behaviour

UI Storia distinguishes between two types of changes:

- **Changes in the `storia/` folder** (YAML files) trigger a **full page reload** — the menu and component metadata are refreshed.
- **Changes in templates or assets** trigger an **iframe-only reload** — the preview is refreshed without touching the page shell.

## Configuring watched paths

By default, UI Storia watches `templates/` and `assets/` for iframe reloads. You can customise this list in your configuration file:

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    watch:
        - '%kernel.project_dir%/templates'
        - '%kernel.project_dir%/assets'
        - '%kernel.project_dir%/src/Twig'
```

The `storia/` folder (defined by `default_path`) is always watched for page reloads and does not need to be listed here.
