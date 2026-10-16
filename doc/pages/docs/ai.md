---
title: AI agents
layout: docs
menu:
  docs:
    weight: 110
---

The YAML files of the `storia` folder are a catalog that a program can read: every component, its variants and its arguments. UI Storia ships a skill for [Claude Code](https://claude.com/claude-code) that uses it, so an agent can look at your design system before it writes a template.

## Two commands for tools

The skill relies on two commands that are also useful in your own scripts.

```bash
bin/console storia:list --format=json
```

Lists every view with its type (`template`, `component` or `form`), its YAML file, its variants and the resolved arguments of each variant. Use `--filter=components/button` or `--filter=components/button:large` to narrow the list. Without `--format=json`, the output is a readable summary.

```bash
bin/console storia:render components/button plain
```

Prints the HTML of a variant on the standard output. `--format=twig` prints the source of the template and `--format=include` prints the code that includes a component. On error (unknown view or variant, Twig or PHP exception), the message goes to the error output and the exit code is `1`.

## Install the skill

The skill is the `skills/ui-storia` folder of the bundle. Copy it to your project, or link it, so Claude Code finds it in `.claude/skills/ui-storia`:

```bash
mkdir -p .claude/skills
cp -r vendor/iq2i/storia-bundle/skills/ui-storia .claude/skills/ui-storia
```

```bash
# or keep it in sync with the installed version of the bundle
ln -s ../../vendor/iq2i/storia-bundle/skills/ui-storia .claude/skills/ui-storia
```

The content of the skill follows the version of the bundle: copy it again after an upgrade, or use the link. Commit `.claude/skills` to share it with your team.

## What the skill does

- Lists the components and variants of the project, and shows what a variant renders.
- Knows the format of the YAML files and the `!data`, `!repeat` and `!lorem` tags, so it can create a component and its variants.
- Runs `storia:test` after a change and reads its output.

## What it does not do

- It does not see the page: it reads HTML and code, not screenshots. It cannot tell you that a component looks wrong.
- It does not decide your design. It follows the components and variants that already exist, so a catalog with few variants gives it little to work with.
- It does not replace a review of the snapshots: an agent can write a variant and its snapshot, a person still decides that the result is right.
