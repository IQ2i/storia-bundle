# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project adheres to [Semantic Versioning](https://semver.org/). The [upgrade guide](https://ui-storia.com/docs/upgrade/) explains how to migrate.

## [Unreleased]

### Breaking changes

- UI Storia is now enabled when `kernel.debug` is true. Set `iq2i_storia.enabled: true` to keep it on an environment running without debug.
- The `iq2i_storia_menu()` Twig function is removed. The sidebar is built by the bundle.
- A form variant declared in YAML now overrides the global `options` of the view (it used to be the opposite for the automatic variants).

### Added

- **Static export**: `storia:export` generates the catalog as a static website with its assets.
- **Shared data**: named data files in `storia/data`, with the `!data`, `!repeat` and `!lorem` tags, and PHP data providers with `!data Class::method`.
- **Controls**: a tab to edit the arguments of the current variant and see the result, deduced from the component props, its PHP class or the `{% types %}` of a page.
- **Variants as tests**: `storia:test` renders every variant and compares it with an HTML snapshot, `StoriaVariants` and `StoriaTestCaseTrait` bring it to PHPUnit.
- **AI agents**: `storia:list` and `storia:render` commands, and an agent skill in `skills/ui-storia` and tools for Symfony Mate (`storia-list`, `storia-render`, `storia-test`).
- Custom variants for form views, with `options` and `with_errors`.
- Documentation split into pages, comparison with Storybook and Fractal, getting started guide, YAML reference, compatibility policy.
- Public demo built from the test application.

### Changed

- `symfony/form` and `symfony/ux-twig-component` are optional: the bundle compiles without them and only the matching view types are unavailable.
- The live reload stream closes after 55 seconds and the browser reconnects by itself.
- The `name` of a variant is now used as the label of its tab, as documented.
- Resolving a bare `Class::method` string is deprecated, use `!data Class::method`.

### Fixed

- A view without any variant renders its page instead of crashing.
- The iframe layout no longer requests favicons that do not exist.

## [0.5.0] - 2026-03-25

### Added

- Live reload with the `storia:watch` command.
- `include:` and `extends:` in YAML files.

### Changed

- `symfony/ux-twig-component` is no longer required.

## [0.4.3] - 2026-03-24

### Fixed

- Scroll of the resized panel.

## [0.4.2] - 2026-03-11

### Fixed

- The original Twig content is used for rendering.

## [0.4.1] - 2026-03-11

### Fixed

- The argument resolver compiler pass builds its service locator from the Storia YAML files.
- Use an interface instead of the Symfony implementation.

## [0.4.0] - 2026-02-10

### Added

- Argument resolver: create arguments from PHP class methods.
- PHP 8.5 and Symfony 8 support.

## [0.3.0] - 2024-11-23

### Added

- Preview of form fields with Symfony Form Types.
- PHP 8.4 support.

### Fixed

- Watch of the Sass files when building the assets.

## [0.2.1] - 2024-08-21

### Added

- Documentation website.

### Changed

- Menu labels are humanized.
- View builders are split into separate classes.

## [0.2.0] - 2024-07-20

### Added

- Twig functions to read the arguments and blocks of a variant.
- Twig Components in sub-folders.
- Parameters passed to page templates.

### Changed

- Components and stories are renamed "views", with a refactoring of the builders and the bundle extension.
- Syntax highlighting is done with highlight.js again, for performance on large content.
- New Symfony versions are tested in CI.

## [0.1.0] - 2024-03-27

First public version: a sidebar to browse the components and pages described in YAML files, variants, a preview in an iframe, tabs with the Twig code, the HTML and Markdown notes, resizable panels, Twig Component support and the ability to disable the bundle.

[Unreleased]: https://github.com/IQ2i/storia-bundle/compare/0.5.0...HEAD
[0.5.0]: https://github.com/IQ2i/storia-bundle/compare/0.4.3...0.5.0
[0.4.3]: https://github.com/IQ2i/storia-bundle/compare/0.4.2...0.4.3
[0.4.2]: https://github.com/IQ2i/storia-bundle/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/IQ2i/storia-bundle/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/IQ2i/storia-bundle/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/IQ2i/storia-bundle/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/IQ2i/storia-bundle/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/IQ2i/storia-bundle/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/IQ2i/storia-bundle/releases/tag/0.1.0
