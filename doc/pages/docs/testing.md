---
title: Testing
layout: docs
menu:
  docs:
    weight: 100
---

Every variant is a test case: the integrator writes the variants, the developer overrides an anonymous component with a PHP class, and `storia:test` catches the regressions.

```bash
bin/console storia:test
```

The command renders every variant of every component and page, then compares the HTML with its snapshot:

```
✓ components/badge:default
≠ components/badge:large
✗ components/product:expensive
    Twig\Error\RuntimeError: Variable "price" does not exist.

41 variants, 39 passed, 0 new, 0 updated, 2 failed
```

- `✗` the variant cannot be rendered (Twig or PHP error). It always fails the command.
- `≠` the rendering differs from the snapshot. A diff is shown (install `sebastian/diff` to get it) and the command fails.
- `+` the variant has no snapshot yet: it is written and the command does not fail, except in CI (see below).
- `↻` the snapshot was rewritten because of `--update`.
- `?` a view defines no variant, or a snapshot has no variant any more: nothing is tested. A view without variant fails the command in CI.

![storia:test on a passing run, a changed snapshot with its diff and a variant that fails to render](/media/testing-output.webp)

The exit code is `1` as soon as one variant fails, `0` otherwise, so the command can be used as is in a CI job.
When the `CI` environment variable is set (GitHub Actions and GitLab CI set it), or with `--ci`, a missing snapshot is an error instead of being written: a file written on a runner is lost, and the variant would never be compared.

```yaml
# .gitlab-ci.yml

storia:
    stage: test
    script:
        - composer install --no-interaction --prefer-dist
        - php bin/console storia:test
```

## Snapshots

A snapshot is the HTML of one variant, without the preview page around it, stored in `tests/storia/snapshots/<view>/<variant>.html`. Commit them: they are reviewed in your merge requests like any other file.
Only line endings and trailing spaces are normalized, nothing else is reformatted.
The HTML also depends on the rendering stack: upgrading Symfony or Twig can change a form theme or an attribute, review the diff and accept it with `--update`.

Snapshots are never rewritten silently. When a change is intended, accept it explicitly:

```bash
bin/console storia:test --update
```

A snapshot whose variant no longer exists is reported with `?` but does not fail the command; delete the file.

Snapshots can only be stable if the rendering is: `!repeat` and `!lorem` are deterministic, but a form with CSRF protection, or a PHP data provider returning dates or random values, is not. Disable the CSRF token in the test environment, make such data deterministic, or use `--no-snapshots` for it.

```yaml
# config/packages/iq2i_storia.yaml

iq2i_storia:
    test:
        snapshot_path: '%kernel.project_dir%/tests/storia/snapshots'
```

## Options

| Option | Description |
| --- | --- |
| `--filter=<text>` | Only test the views or variants whose `view` or `view:variant` name contains the text |
| `--update` | Rewrite the snapshots that differ |
| `--no-snapshots` | Only check that every variant renders |
| `--ci` | Fail on a missing snapshot instead of writing it (default when the `CI` environment variable is set) |
| `--snapshot-path=<dir>` | Use another snapshot directory |
| `-v` | Show the stack trace of the errors |

## PHPUnit

For custom assertions, expose the variants as a data provider and use the `StoriaTestCaseTrait` in a `KernelTestCase`:

```php
use IQ2i\StoriaBundle\Test\PHPUnit\StoriaTestCaseTrait;
use IQ2i\StoriaBundle\Test\PHPUnit\StoriaVariants;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class StoriaTest extends KernelTestCase
{
    use StoriaTestCaseTrait;

    #[DataProvider('provideVariants')]
    public function testVariant(string $path, string $variant): void
    {
        $view = $this->assertVariantRenders($path, $variant);

        self::assertStringNotContainsString('TODO', (string) $view->getHtmlContent());
    }

    public static function provideVariants(): array
    {
        return StoriaVariants::fromDirectory(__DIR__.'/../storia');
    }
}
```

`StoriaVariants::fromDirectory()` reads the YAML files without booting the kernel, as a data provider runs before any test. The `StoriaVariants::provide()` method can also be used with `#[DataProviderExternal]`: it reads the directory from the `STORIA_PATH` environment variable, or `./storia`.
`assertVariantMatchesSnapshot()` compares a variant with its snapshot.

Screenshots (Panther) and accessibility checks are not covered yet.
