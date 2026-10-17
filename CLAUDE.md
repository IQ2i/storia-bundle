# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**UI Storia** is a Symfony bundle that provides a visual and modular development environment for UI components. It allows developers to work directly with project templates and Twig components, unlike Storybook/Fractal which port Twig to JS.

- **Type**: Symfony Bundle (type: `symfony-bundle`)
- **Namespace**: `IQ2i\StoriaBundle`
- **PHP Requirements**: PHP 8.3+ (supports up to PHP 8.5)
- **Symfony Support**: Symfony 6.4, 7.x, and 8.x
- **Optional dependencies**: `symfony/form` and `symfony/ux-twig-component`. The bundle must compile without them, only the matching view types are unavailable.
- **Documentation**: https://ui-storia.com/ (sources in `doc/`, built with Cecil)

## Development Commands

All commands run through Docker Compose. The project uses a Makefile for common tasks.

### Docker
```bash
make build       # Build Docker images
make up          # Start containers in detached mode
make down        # Stop containers
make php         # Connect to PHP container shell
make node        # Connect to Node container shell
```

### Testing & Quality
```bash
make test        # Run PHPUnit tests
make static      # Run PHPStan + PHP CS Fixer (CS Fixer fixes the files)
make rector      # Run Rector for automated refactoring
make ci          # Run all: static + rector + test
```

### Frontend Assets
```bash
make assets/build   # Build JS/CSS with Rollup
make assets/watch   # Watch and rebuild on changes
```

Compiled assets in `public/` are committed: rebuild them after any change in `assets/`.

### Running Individual Commands
For more control, use Docker commands directly:
```bash
# Run specific test file
docker compose exec -w /srv/app php php vendor/bin/phpunit tests/Controller/ViewControllerTest.php

# Run PHPStan only
docker compose exec -w /srv/app php php -d memory_limit=-1 vendor/bin/phpstan analyse

# Run PHP CS Fixer in dry-run mode
docker compose exec -w /srv/app php php vendor/bin/php-cs-fixer fix --dry-run
```

## Architecture

### Bundle Structure

The bundle follows Symfony's AbstractBundle pattern. `src/IQ2iStoriaBundle.php` defines the configuration tree and registers most services by hand (`loadExtension()`), the rest lives in `config/services.php`.

- **Entry Point**: `src/IQ2iStoriaBundle.php` - Bundle configuration and DI setup
- **Configuration**: `config/services.php`, `config/routes.php` and `config/mate.php`
- **Bundle options**: `default_path`, `enabled` (defaults to `kernel.debug`), `watch`, `export` (`output_dir`, `public_dir`, `assets`), `test` (`snapshot_path`)
- **Routes**: `/changes`, `/iframe/{view}` and `/{view}`, all conditioned on the `iq2i_storia.enabled` parameter

### View Builder System

The core architecture uses a **builder pattern with tagged services** (`iq2i_storia.builder`):

1. **ViewBuilder** (`src/View/ViewBuilder.php`): Main orchestrator
   - Reads YAML configuration from `%iq2i_storia.default_path%/{view}.yaml`, through `YamlPreProcessor` (`include` / `extends`, realpath-checked)
   - Processes config through `ViewConfiguration` (Symfony Config component)
   - Delegates to appropriate builder based on `supports()` check
   - Accepts overrides (used by the Controls tab) and returns a `View` DTO with variants
   - `ViewFinder` lists the view paths, `ViewRenderer` renders the shell and iframe pages

2. **Builders** (all extend `AbstractBuilder`):
   - **ComponentBuilder**: Handles Symfony UX Twig Components
   - **FormBuilder**: Handles Symfony Forms
   - **TemplateBuilder**: Handles plain Twig templates
   - Each builder implements `BuilderInterface::supports()` and `build()`

3. **View Configuration** (`src/Config/ViewConfiguration.php`):
   - Defines YAML structure for view definitions
   - Supports: `template`, `component`, `form`, `variants`, `args`, `blocks`, `options`
   - Variants define different states/variations of a component

4. **ArgResolver** (`src/View/Resolver/ArgResolver.php`) resolves service/static references in args. `ArgResolverPass` scans the YAML files at container build time to expose the needed services.

### Data

`src/Data/` provides named data shared by views (`storia/data/*.yaml`):
- **DataRepository** / **DataResolver**: load and resolve the `!data`, `!repeat` and `!lorem` YAML tags, and PHP providers (`!data Class::method`)
- Circular references and unknown names raise dedicated exceptions (`src/Data/Exception/`)

### Controls

`src/Controls/` powers the Controls tab (edit the variant args from the UI): props are deduced from the component props (`PropsExtractor`, `ComponentIntrospector`), its PHP class or the `{% types %}` of a page, then turned into `Control` objects by `ControlsBuilder` / `ControlFactory`. `OverridesParser` reads the user edits.

### Controllers

- **ViewController** (`src/Controller/ViewController.php`): Main UI with menu navigation
- **IframeController** (`src/Controller/IframeController.php`): Isolated component preview
- **ChangesController** (`src/Controller/ChangesController.php`): SSE stream for the live reload (closes after 55 s, the browser reconnects)
- Links are produced through `LinkGeneratorInterface` (`RouterLinkGenerator` for the UI, `StaticLinkGenerator` for the export)

### Menu System

- **MenuBuilder** (`src/Menu/MenuBuilder.php`): Scans storia directory for `.yaml` files
- Builds hierarchical menu structure from file paths
- DTOs: `Menu` and `MenuItem` for type safety

### Console Commands (`src/Command/`)

- `storia:watch`: runs the native watcher (`tools/watcher`) and writes `var/storia_reload.json` for the live reload
- `storia:export`: static export of the catalog (`src/Export/`: `StaticExporter`, `AssetCollector`, `UrlRewriter`), also used for the public demo
- `storia:test`: renders every variant and compares it with an HTML snapshot (`src/Test/`: `VariantRunner`, `Snapshot\SnapshotStore`, `Snapshot\SnapshotNormalizer`)
- `storia:list` / `storia:render`: describe the catalog (`src/Catalog/CatalogDescriber`) and print a variant, mainly for AI agents

### Testing helpers for projects

`src/Test/PHPUnit/` (`StoriaVariants` data provider, `StoriaTestCaseTrait`) brings the variants to the PHPUnit suite of the projects using the bundle.

### AI agents (Symfony Mate)

- `src/Mate/` (`StoriaTool`, `ConsoleRunner`) exposes `storia-list`, `storia-render` and `storia-test` to Symfony Mate, wired by `config/mate.php`
- `INSTRUCTIONS.md` and `skills/ui-storia/` are the instructions and the agent skill shipped to the projects (declared in `composer.json` under `extra.ai-mate`). They describe the bundle for its users, not this repository.

### Twig

- **ViewExtension** (`src/Twig/ViewExtension.php`): Twig functions used by the storia templates
- `src/Util/Label.php`: humanizes names for labels

### Frontend

- **Build Tool**: Rollup.js with plugins for Sass and file copying
- **JS Framework**: Hotwired Stimulus (controllers in `assets/js/controllers/`: menu, tabs, controls, resize, clipboard, highlight, live reload)
- **Output**: `public/storia.js` and `public/storia.css`
- **External Dependencies**: Stimulus, Highlight.js (for syntax highlighting)

### Test Application

`tests/TestApplication/` contains a full Symfony application for integration testing:
- **Kernel**: `tests/TestApplication/src/Kernel.php`
- **Storia Examples**: `tests/TestApplication/storia/` (`components/`, `pages/`, `data/`, `_shared.yaml`), also used as the public demo
- **Alternate catalogs**: `storia_broken/` and `storia_empty/` for the error and empty-catalog cases
- **Templates**: `templates/`, `templates_documented/`, `templates_plain/`
- PHPUnit is configured to use this test app kernel
- `config/reference.php` and `var/` are generated and git-ignored

## Code Quality Standards

### PHP CS Fixer
- **Ruleset**: `@Symfony` + `@Symfony:risky` + `@PHP83Migration`
- **Strict Types**: All files must have `declare(strict_types=1);`
- **Header Comment**: MIT license header required on all PHP files
- Excludes: `skeleton/` and `vendor/`

### PHPStan
- **Level**: 5 (PHPStan 2)
- **Extensions**: symfony/phpstan-symfony for Symfony-specific rules
- **Scope**: Only `src/` directory (tests excluded)

### Rector
- **Sets**: CODE_QUALITY, CODING_STYLE, PHP 8.3, Symfony and PHPUnit sets deduced from `composer.json`
- **Scope**: `config/`, `src/` and `tests/`
- **Skips**: TestApplication directory and specific risky rectors

### CI
GitHub Actions (`.github/workflows/ci.yaml`) runs the quality tools and the tests; `doc.yaml` builds the documentation site.

## Key Patterns

1. **Builder Pattern**: All view types (component/form/template) use tagged builder services
2. **DTO Pattern**: Immutable DTOs for View, Variant, Menu, MenuItem
3. **Readonly Classes**: Modern PHP readonly classes where appropriate (ViewBuilder, VariantRunner, MenuBuilder)
4. **Symfony Config Component**: YAML configs processed through Symfony's Configuration system
5. **Compiler Passes**: `ProfilerPass` for Web Profiler integration, `ArgResolverPass` for the services referenced in the YAML files
6. **Trust boundary**: the `storia/` YAML files are treated as code (args and blocks are rendered as Twig, `Class::method` references are called)

## Development Workflow

1. Start Docker environment: `make up`
2. Make code changes in `src/`
3. Run tests: `make test`
4. Fix code style: `make static`
5. For frontend changes: `make assets/watch` in separate terminal

## Testing Notes

- Tests use a real Symfony application kernel for integration testing
- `tests/` mirrors `src/` (Command, Config, Controller, Controls, Data, DependencyInjection, Export, Mate, Menu, Test, Twig, Url)
- Shared fixtures in `tests/Fixtures/` (including `storia_data/`), Twig fixtures in `tests/Twig/fixtures/`
- Functional tests use `BrowserKit` for HTTP client simulation
- All tests should pass before committing

## File Organization

- `src/`: Bundle source code (tagged services, controllers, builders, commands)
- `config/`: Bundle service, route and Mate configuration
- `templates/`: Bundle templates for storia UI
- `assets/`: Frontend source (JS/Sass)
- `public/`: Compiled frontend assets
- `tests/`: Unit tests and TestApplication
- `skeleton/`: Template files for generating new components/templates
- `skills/`, `INSTRUCTIONS.md`: Agent skill and instructions shipped with the bundle
- `tools/watcher/`: Native file watcher binaries used by `storia:watch`
- `doc/`: Documentation site (Cecil), `CHANGELOG.md`: Keep a Changelog format
