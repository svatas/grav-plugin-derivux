# Derivux by Gravister - Project State

> **Last updated:** 2026-09-25  
> **Purpose:** authoritative handoff for subsequent Chat and Work iterations. This document records only approved decisions and verified repository state.

## Current baseline

- **Current version / baseline:** `0.1.0` — initial repository bootstrap.
- **Repository:** `svatas/grav-plugin-derivux`.
- **Intended primary branch:** `main`.
- **Current GitHub default branch:** `master` until manually changed in repository settings. The `main` branch contains the current Derivux baseline and architecture documentation.
- **License:** MIT.
- **Product-facing name:** **Derivux by Gravister**.
- **Technical plugin slug:** `derivux`.
- **Supported Grav generation:** Grav 2.x (`>=2.0.0` declared in the plugin blueprint).
- **Development status:** very early Derivux development; repository governance and minimal plugin skeleton exist. Functional theme derivation has not yet been migrated into Derivux.

## Product definition

Derivux is intended to create and maintain **minimal derived Grav themes** using Grav's native theme inheritance rather than copying an entire parent theme.

The architectural goal is to generate only the files and metadata required for the derived theme to differ from its parent. Parent-theme files that do not need to change should remain inherited rather than duplicated.

## Approved architectural principles

- Prefer Grav-native theme inheritance over copying a complete parent theme.
- Generated themes should be minimal, understandable and maintainable.
- Do not introduce unnecessary parallel infrastructure when Grav already provides a suitable native mechanism.
- Derivux is a standalone Gravister product/tool; integration with Elementux may be designed later but is not part of the current baseline.
- Clonux may be used as verified legacy source and behavioral input, but it must not be copied blindly into Derivux.
- Preserve non-destructive preflight and explicit impact planning before filesystem mutation.
- Preserve the no-overwrite, no-parent-modification, no-automatic-activation safety contract unless explicitly changed later.

## 0.1.0 scope completed

- Repository initialized.
- `main` branch created from the initial repository commit.
- MIT license added.
- `.gitignore` added.
- `CHANGELOG.md` added.
- Minimal Grav plugin entry point `derivux.php` added.
- Default plugin configuration `derivux.yaml` added.
- Grav plugin metadata/configuration blueprint `blueprints.yaml` added.
- This authoritative state document added.
- **Clonux → Derivux source audit completed and recorded in `CLONUX-AUDIT.md`.**

## Verified Clonux source baseline

A complete source package was supplied and inspected:

- **Package:** `gravister-clonux-20260924221838.zip`
- **Product/version:** **Clonux by Gravister 0.6.1**
- **Role:** verified legacy functional baseline / behavioral input for Derivux migration.

The previous statement that no verifiable Clonux implementation was available is superseded.

### Important verified findings

- Clonux 0.6.1 is already an **inheritance-first Grav 2 scaffolder**, not a full-theme copier.
- It generates an inherited theme stream with child lookup before parent lookup.
- Generated child PHP extends the parent theme class.
- Generated child blueprint extends the parent theme blueprint.
- It has an Admin2 source → target → review → done workflow.
- It exposes API routes for localization, theme discovery, validation/dry-run and creation.
- It refuses to overwrite an existing target, does not modify the parent and does not auto-activate the child.
- It does not execute shell/npm/Tailwind/Vite/PostCSS builds.
- It detects build-system indicators and presents guidance.
- It includes EN/CS localization and generated preview support.

### Audit concerns to improve during migration

- Parent PHP class names are currently inferred mechanically from slugs; this should be made safer.
- Parent YAML configuration is copied into the child minus the `streams` block; whether this snapshot behavior is desirable must be runtime-tested before changing it.
- The filesystem executor stops on error but does not roll back files/directories already created in that operation.
- Preview generation is feature-rich but relatively large/complex; required formats should be verified against current Admin2 before simplification.
- The controller combines many responsibilities and should be refactored into testable services while preserving external behavior.

## Revised next functional milestone — decision gate

The proposed next version remains **0.2.0**, but its meaning changes from greenfield implementation to:

**Clonux 0.6.1 controlled migration + Derivux identity + targeted architecture cleanup.**

The recommended migration boundary is:

1. Capture Clonux 0.6.1 behavior in tests/specification before semantic changes.
2. Migrate product/runtime identity from Clonux to Derivux consistently.
3. Preserve proven Admin2 workflow and safety behavior.
4. Split core responsibilities into testable services rather than copying the monolithic controller unchanged.
5. Improve parent-class resolution and generation rollback/cleanup.
6. Verify child YAML/config behavior, blueprint inheritance and preview requirements on current Grav 2/Admin2 before removing or redesigning them.

Do **not** add unrelated new features during this migration.

## Not implemented yet in Derivux

- Clonux functional runtime migrated under Derivux identity.
- Parent-theme discovery and selection.
- Theme metadata inspection.
- Derived-theme generation.
- Admin2 derivation UI.
- Dry-run/impact plan.
- Transaction-like generation cleanup.
- Automated behavior/regression tests.
- Release packaging.

## Open / unverified items

- **Runtime loading on Grav 2:** NEOVĚŘENO for the Derivux 0.1.0 skeleton.
- **PHP lint:** NEOVĚŘENO for current Derivux source in this GitHub-only bootstrap iteration.
- **Blueprint behavior in current Admin2:** NEOVĚŘENO for Derivux.
- **Clonux 0.6.1 runtime behavior on the current target Grav/Admin2 versions:** source behavior is verified by inspection, but current-environment runtime acceptance is NEOVĚŘENO here.
- **Default branch migration:** `main` exists, but GitHub still reports `master` as repository default until changed manually.
- Exact safe parent PHP class/namespace resolution rules require implementation/testing.
- Whether parent theme configuration should continue to be copied into child YAML or be made thinner remains an explicit design/test question.
- Which generated preview formats current Admin2 actually requires remains NEOVĚŘENO.

## Decisions that must not be silently reverted

- Product-facing name is **Derivux by Gravister**.
- License is MIT.
- The target branch naming is `main` rather than `master`.
- Derivux should produce minimal derived themes through Grav-native inheritance, not full parent-theme copies by default.
- Use Clonux 0.6.1 as verified legacy behavioral input, not as a requirement to preserve every internal implementation detail.
- Do not couple Derivux runtime to Elementux unless a later explicit architecture decision requires it.
- Do not overwrite an existing target theme in the derivation workflow.
- Do not automatically activate generated themes.
- Do not execute build tools or shell commands as part of the default derivation workflow.

## Recommended next work

1. Change the GitHub repository default branch from `master` to `main` and, once confirmed safe, remove the obsolete `master` branch if desired.
2. Preserve the supplied Clonux 0.6.1 package as the migration reference artifact.
3. Before functional migration, define behavior/regression checks for the verified Clonux contract.
4. Implement **0.2.0** as a controlled migration into Derivux identity, preserving the external safe workflow while refactoring internals into testable services.
5. Add parent-class resolution and target-directory rollback/cleanup as correctness improvements within that migration.
6. Runtime-test on Grav 2/Admin2 before deciding whether to change parent-config copying or preview formats.

## Handoff log

| Date | Version / baseline | Scope | Verification | Notes |
| --- | --- | --- | --- | --- |
| 2026-09-25 | `0.1.0` | Repository bootstrap, MIT license, minimal Grav 2 plugin skeleton, project-state governance. | GitHub writes succeeded. Runtime/PHP/Admin2 verification remains NEOVĚŘENO. | `main` created; GitHub default branch still needs manual change from `master` to `main`. |
| 2026-09-25 | `0.1.0` documentation pass | Initial architecture audit before Clonux source was available. | GitHub/account search and current repository state inspected. | Superseded by recovered Clonux 0.6.1 source audit. |
| 2026-09-25 | `0.1.0` Clonux source audit | Inspected supplied Clonux 0.6.1 package; revised migration strategy from greenfield to controlled behavioral migration/refactor. | Source tree, plugin entry point, controller, Admin2 UI, EN/CS, blueprint/config and README/CHANGELOG inspected. | No Derivux runtime code migrated yet. |
