# Derivux by Gravister

[![Version](https://img.shields.io/badge/version-0.6.2-blue.svg)](#version)
[![Grav](https://img.shields.io/badge/Grav-2.x-7B1FA2.svg)](https://getgrav.org/)
[![Admin2](https://img.shields.io/badge/Admin2-2.x-2563eb.svg)](https://github.com/getgrav/grav-plugin-admin2)
[![API](https://img.shields.io/badge/API-1.x-059669.svg)](https://github.com/getgrav/grav-plugin-api)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.3-777BB4.svg)](https://www.php.net/)
[![CI](https://github.com/svatas/grav-plugin-derivux/actions/workflows/ci.yml/badge.svg)](https://github.com/svatas/grav-plugin-derivux/actions/workflows/ci.yml)
[![License](https://img.shields.io/github/license/svatas/grav-plugin-derivux)](LICENSE)

**Derivux by Gravister** is an Admin2 helper for Grav 2 that creates a minimal derived theme from an already installed parent theme using Grav's native theme inheritance.

For example:

```text
typhoon → typhoon-custom
```

Derivux is deliberately conservative. It scaffolds the child theme, keeps the parent untouched and leaves activation under your control.

## Version

Current release: **0.6.2**

The release is aligned with the current Grav 2 architecture and has been statically audited against the current public Grav/Admin2/API lines. Runtime acceptance on a real target installation is still required before the project is declared 1.0-ready.

## Requirements

- Grav **2.x**
- PHP **8.3+**
- Admin2 **2.x**
- Grav API plugin **1.x**

The plugin uses the Admin2 plugin-page extension points and API plugin custom routes. It is not intended for the Grav 1.x Classic Admin stack.

## What Derivux creates

For a target such as `typhoon-custom`, Derivux creates:

```text
user/themes/typhoon-custom/
├── blueprints.yaml
├── typhoon-custom.yaml
├── typhoon-custom.php
├── README.md
├── screenshot.svg
├── thumbnail.svg
├── preview.svg
├── screenshot.png
├── thumbnail.png
├── preview.png
├── screenshot.jpg
├── thumbnail.jpg
└── preview.jpg
```

The generated theme:

- extends the selected parent theme class;
- extends the parent theme blueprint in Admin2;
- uses a Grav `ReadOnlyStream` with the child path before the parent path;
- inherits parent templates and assets instead of copying them;
- includes distinct SVG and pure-PHP PNG previews;
- keeps JPG preview files only as generic fallbacks.

## Safety model

Derivux does **not** modify the parent theme and does **not** overwrite an existing target directory.

It also does not:

- activate the new theme automatically;
- clear the Grav cache;
- copy the parent theme tree;
- copy Premium theme files;
- run shell commands;
- run npm, Tailwind, Vite, PostCSS or another build process.

Before creation, the UI performs a dry-run and shows the planned filesystem operations. If creation fails after the new target directory has been created, Derivux removes only that newly created target directory.

All generated paths are constrained to:

```text
user/themes/<target>/
```

## Parent theme compatibility

Derivux resolves the parent theme PHP class from the installed parent theme source instead of assuming that the class name can always be derived from the directory slug. If the parent class cannot be identified safely, creation stops before writing the child theme.

This is intentional: a failed preflight is preferable to generating a theme whose PHP inheritance is silently wrong.

## Typhoon, Helios and compiled CSS

A derived theme can inherit an already compiled parent stylesheet, so ordinary configuration changes such as colors, fonts, presets and existing theme options normally need no additional build step.

If you later override templates and introduce utility classes that do not exist in the parent theme's compiled CSS, the child theme may need its own CSS build pipeline.

Derivux does not generate or execute such a pipeline automatically. This keeps the default workflow usable on shared hosting and avoids running developer tooling from an administration plugin.

## Premium themes

Derivux only works with a parent theme that is already installed legitimately on the site. It does not include, redistribute, replace or bypass Premium theme files or licensing.

## Admin2 and API integration

Derivux follows the same current Grav 2 integration direction used by the wider Gravister toolset:

- Admin2 plugin page rather than a Classic Admin page;
- `window.__GRAV_I18N` for active Admin locale;
- `window.__GRAV_API_TOKEN` and the configured API base/prefix;
- explicit API permissions for read and create operations;
- API route/controller loading compatible with cached API dispatchers;
- no hard-coded `/admin` route in post-creation navigation.

## Installation

Install the plugin as:

```text
user/plugins/gravister-derivux/
```

For a Direct Install package, the ZIP must contain one root directory named `gravister-derivux/`.

After installation, open **Derivux by Gravister** in Admin2.

## Basic workflow

```text
1. Select an installed parent theme.
2. Enter the technical slug for the derived theme.
3. Review the dry-run, safety notes and generated operations.
4. Create the derived theme.
5. Open the generated theme in Admin2 and review its configuration.
6. Activate it manually when ready.
```

## Current project status

Version 0.6.2 is a pre-1.0 release with the primary workflow already implemented. The remaining work before 1.0 is mainly runtime verification, compatibility coverage, documentation polish and release hardening rather than a new architectural rewrite.

See `DERIVUX-STATE.md` for the current engineering handoff and known unverified items.

## License

MIT. See [LICENSE](LICENSE).
