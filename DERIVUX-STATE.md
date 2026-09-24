# Derivux by Gravister - Project State

> **Last updated:** 2026-09-25  
> **Purpose:** authoritative handoff for subsequent Chat and Work iterations. This document records only approved decisions and verified repository state.

## Current baseline

- **Current version / baseline:** `0.1.0` — initial repository bootstrap.
- **Repository:** `svatas/grav-plugin-derivux`.
- **Intended primary branch:** `main`.
- **Current GitHub default branch:** `master` until manually changed in repository settings. The `main` branch has been created and contains the 0.1.0 bootstrap and current architecture documentation.
- **License:** MIT.
- **Product-facing name:** **Derivux by Gravister**.
- **Technical plugin slug:** `derivux`.
- **Supported Grav generation:** Grav 2.x (`>=2.0.0` declared in the plugin blueprint).
- **Development status:** very early development; repository and minimal plugin skeleton exist, but theme derivation functionality is not implemented yet.

## Product definition

Derivux is intended to create and maintain **minimal derived Grav themes** using Grav's native theme inheritance rather than copying an entire parent theme.

The architectural goal is to generate only the files and metadata required for the derived theme to differ from its parent. Parent-theme files that do not need to change should remain inherited rather than duplicated.

## Approved architectural principles

- Prefer Grav-native theme inheritance over copying a complete parent theme.
- Generated themes should be minimal, understandable and maintainable.
- Do not introduce unnecessary parallel infrastructure when Grav already provides a suitable native mechanism.
- Derivux is a standalone Gravister product/tool; integration with Elementux may be designed later but is not part of the current baseline.
- The old Clonux concept may be used as historical/product input, but its implementation must not be copied blindly into Derivux.
- Non-destructive preflight and explicit impact planning are preferred before filesystem mutation.

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
- **Clonux → Derivux architecture audit completed and recorded in `CLONUX-AUDIT.md`.**

## Clonux audit result

- No verified Clonux source implementation is available in the current connected GitHub/project material for mechanical migration or rename.
- The repository now named `svatas/grav-plugin-derivux` was empty before the Derivux bootstrap.
- No separate source-bearing `Clonux by Gravister` repository was found in the connected GitHub account/search.
- Therefore current Derivux code is **not** renamed Clonux code, and no historical source compatibility requirement has been established.
- Clonux remains useful only as conceptual product history: guided, safe theme cloning/migration is being narrowed into a native inheritance-first derivation workflow.

## Proposed next functional milestone — decision gate

A detailed proposal is recorded in `CLONUX-AUDIT.md` for **0.2.0 — Minimal Theme Derivation**.

The proposed MVP is intentionally limited to:

1. discover/select one installed parent theme;
2. capture a new derived-theme identity;
3. preflight parent, target slug/path and non-overwrite conditions;
4. produce an exact impact plan before mutation;
5. generate only the minimal derived-theme files required for Grav-native inheritance;
6. validate the generated theme and clean up only the newly created target if generation fails.

The proposal explicitly defers automatic site-theme switching, arbitrary override copying, parent-update synchronization, historical Clonux import, Elementux/Canvas integration and GPM/repository publishing.

**No 0.2.0 implementation should begin until this workflow boundary is explicitly accepted or amended.**

## Not implemented yet

- Parent-theme discovery and selection.
- Theme metadata inspection.
- Derived-theme generation.
- Validation of parent/derived theme relationships.
- Admin UI.
- Preview or impact plan before generation.
- Selective override/file generation.
- Migration/import from Clonux.
- Automated tests and release packaging.

## Open / unverified items

- **Runtime loading on Grav 2:** NEOVĚŘENO. The 0.1.0 skeleton has not yet been installed and loaded on a real Grav 2 instance.
- **PHP lint:** NEOVĚŘENO in this GitHub-only bootstrap iteration.
- **Blueprint behavior in current Admin2:** NEOVĚŘENO.
- **Default branch migration:** `main` exists, but GitHub still reports `master` as the repository default branch until the repository setting is changed manually.
- Exact parent-class/namespace discovery rules for generated inherited themes require implementation research/testing before 0.2.0 code is finalized.
- Exact minimum generated theme file set must be confirmed against real Grav 2 runtime behavior; current proposed boundary is documented but not yet runtime-proven.

## Decisions that must not be silently reverted

- Product-facing name is **Derivux by Gravister**.
- License is MIT.
- The target branch naming is `main` rather than `master`.
- Derivux should produce minimal derived themes through Grav-native inheritance, not full parent-theme copies by default.
- Do not implement Clonux compatibility or migration merely for historical continuity unless explicitly approved.
- Do not couple Derivux runtime to Elementux unless a later explicit architecture decision requires it.
- Do not overwrite an existing target theme in the first derivation workflow.

## Recommended next work

1. Change the GitHub repository default branch from `master` to `main` and, once confirmed safe, remove the obsolete `master` branch if desired.
2. Install the 0.1.0 plugin skeleton on a Grav 2 test instance and verify that Grav recognizes and loads it cleanly.
3. Perform PHP lint and basic plugin metadata/Admin2 checks.
4. Review and approve/amend `CLONUX-AUDIT.md`, especially the proposed 0.2.0 input, preflight, generated-file set, rollback boundary and explicitly deferred scope.
5. Once approved, implement 0.2.0 starting with a UI-independent derivation planning/service layer and tests; add Admin2 UI only after the core operation is deterministic and testable.

## Handoff log

| Date | Version / baseline | Scope | Verification | Notes |
| --- | --- | --- | --- | --- |
| 2026-09-25 | `0.1.0` | Repository bootstrap, MIT license, minimal Grav 2 plugin skeleton, project-state governance. | GitHub writes succeeded. Runtime/PHP/Admin2 verification remains NEOVĚŘENO. | `main` created; GitHub default branch still needs manual change from `master` to `main`. |
| 2026-09-25 | `0.1.0` documentation pass | Clonux heritage/source audit and proposed 0.2.0 Minimal Theme Derivation boundary. | GitHub/account search and current repository state inspected; Grav 2 inheritance documentation reviewed. | No functional code change. 0.2.0 remains behind an explicit approval gate. |
