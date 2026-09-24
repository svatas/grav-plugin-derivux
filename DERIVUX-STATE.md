# Derivux by Gravister - Project State

> **Last updated:** 2026-09-25  
> **Purpose:** authoritative engineering handoff for subsequent Derivux work.

## Current baseline

- **Current version / baseline:** `0.6.2`.
- **Repository:** `svatas/grav-plugin-derivux`.
- **Primary development branch:** `main`.
- **Product-facing name:** **Derivux by Gravister**.
- **Technical plugin slug:** `gravister-derivux`.
- **License:** MIT.
- **Target platform:** Grav 2.x + Admin2 2.x + API 1.x, PHP 8.3+.
- **Development status:** mature pre-1.0. The core derived-theme creation workflow is implemented; 1.0 readiness depends mainly on runtime acceptance and release hardening.
- **Direct Install package:** `gravister-derivux-0.6.2-direct-install.zip`, SHA-256 `7a6986556db1345d83acfeefd2452754ccaa80ddaa360a5c71e576effbb27ad2`.
- **Source package:** `gravister-derivux-0.6.2-source.zip`, SHA-256 `16f0b6e2f62750539ce95de25f1f26b1cbc66ab75deb6df3a1e1ea019f0ddc10`.

## Product definition

Derivux creates a minimal derived Grav theme from an installed parent theme using native theme inheritance.

The generated child keeps its own identity and configuration while inheriting the parent templates/assets through a `ReadOnlyStream`. Parent theme files are not copied as the normal workflow.

## 0.6.2 functional scope

- Discover installed themes from `user/themes`.
- Select a parent theme and derive a suggested target slug.
- Validate source/target identity and refuse overwrite.
- Resolve the actual parent PHP theme class from the installed theme source.
- Keep installed themes visible in discovery while disabling parents whose PHP class cannot be resolved safely.
- Preview planned filesystem operations before mutation.
- Create a derived theme with:
  - parent blueprint inheritance;
  - child-before-parent theme stream;
  - PHP inheritance;
  - generated README;
  - dynamic SVG previews;
  - pure-PHP PNG previews;
  - static JPG fallback previews.
- Report creation operations.
- Do not activate the generated theme automatically.
- Clean up only the newly created target directory if generation fails.

## Architecture / integration decisions

- Admin UI is an Admin2 plugin page.
- UI localization follows active Admin2 locale through `window.__GRAV_I18N`.
- API calls use Admin2-provided API server/prefix/token values.
- API controller extends the current API plugin `AbstractApiController`.
- Permissions:
  - `api.derivux.read`
  - `api.derivux.create`
- Sidebar visibility uses `api.derivux.read`.
- API routes live under `/derivux`.
- The controller is loaded during plugin initialization so cached API dispatchers can still resolve it.
- The plugin has no frontend runtime dependency and does not alter site rendering by itself.

## Compatibility audit

Static review on 2026-09-25 used the current public versions/lines:

- Grav `2.2.0`
- Admin2 `2.1.23`
- API `1.0.40`

Key compatibility findings:

- Grav 2 requires PHP 8.3+, matching the Derivux target.
- The API plugin still documents `onApiRegisterRoutes`, `onApiSidebarItems` and `onApiPluginPageInfo`.
- Admin2 remains API-driven and supports plugin pages.
- Derivux no longer relies on a hard-coded Admin route.
- Derivux follows the same Admin2/API/I18N direction as Elementux.

## Elementux alignment

Derivux and Elementux remain separate products. Derivux does not require Elementux at runtime.

Shared Gravister conventions intentionally aligned in 0.6.2:

- `gravister-*` technical plugin slug pattern;
- Admin2-first administration;
- current API controller/permission pattern;
- active Admin locale rather than a separate language switch;
- EN + CS localization;
- conservative non-destructive mutations;
- explicit engineering state documentation.

No Elementux integration contract is implemented in Derivux 0.6.2.

## Safety decisions that must not be silently reverted

- Never overwrite an existing target theme.
- Never modify the selected parent theme.
- Never copy the entire parent theme by default.
- Never activate a generated theme automatically.
- Never run shell, npm, Tailwind, Vite, PostCSS or similar build commands from the normal creation workflow.
- Keep all generated writes constrained to `user/themes/<validated-target>/`.
- Prefer a failed preflight to guessing an unsafe parent PHP class.
- Keep Derivux independent from Elementux runtime.

## Verification

Completed for 0.6.2 source:

- static PHP syntax/lint;
- YAML parsing;
- JavaScript syntax check;
- product-identity scan;
- package structure audit;
- Direct Install ZIP contains one `gravister-derivux/` root and no development artifacts;
- both release ZIPs pass compressed-data integrity checks;
- current Grav/Admin2/API integration audit;
- GitHub Actions CI passed on the final 0.6.2 source commit.

Still **NEOVĚŘENO** until tested on a real target site:

- full runtime load in Grav 2.2.0;
- Admin2 2.1.23 page rendering and permissions;
- API 1.0.40 runtime route behavior;
- actual theme derivation with Typhoon;
- actual theme derivation with at least one non-Typhoon theme;
- generated child activation/rendering;
- failure rollback under a forced filesystem error.

## Repository housekeeping

- `main` is the intended primary development branch.
- `master` has been synchronized to the same 0.6.2 commit so the repository does not expose stale code while GitHub still reports `master` as default.
- GitHub default branch should be changed manually to `main`; after that `master` may be deleted if desired.
- Repository-level description/About metadata must use only the Derivux identity; this metadata is outside the currently available repository-write connector surface and may require a manual GitHub edit.

## 1.0 readiness

Derivux is close to a 1.0 candidate because the primary product workflow already exists.

Recommended gate before 1.0:

1. Accept 0.6.2 on a current Grav/Admin2/API test installation.
2. Add targeted runtime regression tests around derivation and rollback.
3. Verify at least two structurally different parent themes.
4. Review accessibility/keyboard behavior of the Admin2 workflow.
5. Freeze API/permission identifiers.
6. Produce an RC build and perform clean-install/update/uninstall/package checks.
7. Publish 1.0 only after the RC passes without architecture changes.

## Handoff log

| Date | Version | Scope | Verification |
| --- | --- | --- | --- |
| 2026-09-25 | `0.6.2` | Product identity, complete runtime migration, Grav 2/Admin2/API alignment, permissions, locale modernization, parent-class validation, rollback, CI/package hardening. | Static checks and GitHub CI complete; target-site runtime acceptance remains NEOVĚŘENO. |
