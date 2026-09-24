# Clonux → Derivux audit

> Date: 2026-09-25  
> Source inspected: `gravister-clonux-20260924221838.zip`  
> Source version: **Clonux by Gravister 0.6.1**  
> Status: source audit only; no Clonux runtime code has yet been migrated into Derivux.

## Executive finding

The previous conclusion that no verifiable Clonux implementation was available is superseded. A complete Clonux 0.6.1 source package has now been supplied and inspected.

This changes the migration picture significantly: Clonux is **already an inheritance-first Grav 2 scaffolder**, not a whole-theme copier. Its fundamental product architecture is therefore aligned with Derivux far more closely than previously assumed.

Derivux should not be a blind text rename of Clonux, but Clonux 0.6.1 is now the best available functional baseline and should be treated as verified legacy source for a controlled refactor/rebrand.

## Verified Clonux 0.6.1 architecture

The package contains:

- Grav 2 plugin entry point `gravister-clonux.php`;
- Admin2 page component `admin-next/pages/gravister-clonux.js`;
- API controller `classes/Controller/GravisterClonuxController.php`;
- EN/CS localization;
- plugin blueprint/configuration;
- preview fallback resources;
- placeholder blueprint/template resources;
- README, CHANGELOG and MIT license.

The plugin exposes Admin2/API routes for:

- localization;
- installed-theme discovery;
- dry-run validation/planning;
- theme creation.

The Admin2 UX is a four-step flow: source theme → target theme → review → done.

## What Clonux already does correctly

### Native inheritance, not full cloning

Clonux explicitly does **not** copy the parent theme tree. The generated child YAML uses a Grav `ReadOnlyStream` with this lookup order:

1. `user/themes/<child>`
2. `user/themes/<parent>`

The generated PHP class extends the parent theme class. The generated child blueprint extends the parent theme blueprint using `themes://<parent>`.

That is directly aligned with the approved Derivux principle: **derive minimally, inherit maximally**.

### Safety model

Clonux 0.6.1 already enforces important rules that should survive into Derivux:

- validates source and target slugs;
- refuses source == target;
- requires the parent directory to exist;
- refuses to overwrite an existing target path;
- writes only under `user/themes/<target>`;
- does not modify the parent;
- does not activate the generated theme automatically;
- does not clear cache;
- does not execute shell, npm, Tailwind, Vite or PostCSS commands;
- supports a dry-run/review step before creation.

### Admin2 integration

Clonux already has working product structure for Admin2:

- sidebar item;
- component page registration;
- API routes;
- token-aware API client;
- EN/CS UI;
- source-theme discovery;
- review/report screen;
- post-create navigation.

This is too much proven behavior to discard merely to rebuild the same product under a new name.

### Tailwind/build awareness

The source scans the parent theme for indicators such as:

- `package.json`;
- Tailwind config files;
- PostCSS config;
- Vite config;
- compiled CSS hints.

It then presents guidance without trying to run a build automatically. This is a sound conservative boundary and should remain unless a later explicit feature adds an opt-in developer workflow.

## What should be retained in Derivux

Derivux should preserve, after review/refactoring:

- the source/target/review/done Admin2 flow;
- installed theme discovery;
- target slug validation;
- hard no-overwrite behavior;
- dry-run planning;
- Grav-native inheritance stream generation;
- parent blueprint inheritance;
- parent-theme class inheritance;
- EN/CS UX;
- build-system detection/guidance;
- explicit statement that the parent remains untouched;
- no automatic activation or shell/build execution.

## What should not be copied blindly

### Parent class derivation

Clonux currently derives class names mechanically from slugs. This should be audited before Derivux adopts it as a general solution. A parent theme whose PHP class does not match the slug-derived convention could break the generated child class.

Derivux should inspect/resolve the actual parent theme class where possible and fail safely rather than guess silently.

### Theme configuration copying

Clonux reads the parent theme YAML, removes its `streams` block, and uses the remaining configuration as the starting child YAML before adding inheritance streams.

This is practical, but it means the generated child is not minimal in the strictest sense: it snapshots parent configuration values at creation time. Derivux needs an explicit product decision here:

- preserve this behavior for predictable Admin settings, or
- generate a thinner child config and rely more heavily on inheritance/defaults.

This must be tested against Grav 2/Admin2 behavior before changing it.

### Filesystem transaction behavior

Clonux executes operations sequentially. On a write/copy failure it stops and reports an error, but the inspected implementation does **not** roll back directories/files already created during that attempt.

Derivux should improve this with create/validate/cleanup behavior limited strictly to the new target directory.

### Preview complexity

Clonux generates dynamic SVG and pure-PHP PNG previews plus static JPG fallbacks. This is clever and hosting-friendly, but it is a substantial amount of code and creates nine preview files across three formats.

Derivux should decide whether Admin2 still requires this exact preview strategy. If one or two formats are sufficient in current Grav 2/Admin2, this is a candidate for simplification. Until runtime-tested, do not remove it merely for cleanliness.

### Empty directories and placeholder resources

Clonux creates `templates/`, `css/` and `css/custom/` directories even when no overrides exist, and the plugin package itself includes placeholder modular resources.

These should be re-evaluated against the principle of minimal generated output. Empty directories may be useful onboarding affordances, but they are not required by inheritance itself.

### Controller maintainability

The Clonux controller is highly compact and combines discovery, validation, planning, generation, preview rendering, PNG encoding, localization parsing and filesystem execution in one class.

Derivux should preserve behavior while splitting responsibilities into testable services rather than perpetuating a monolithic controller.

## Revised Derivux implementation strategy

The recommended next functional version remains **0.2.0**, but it should now be framed as **Clonux 0.6.1 controlled migration + architectural cleanup**, not a greenfield reimplementation.

### Phase 1: behavioral baseline

Capture Clonux 0.6.1 behavior as tests/specification before changing semantics:

- source-theme discovery;
- target validation;
- no-overwrite;
- planned operation list;
- inherited YAML stream;
- parent blueprint extension;
- generated PHP class relationship;
- no automatic activation;
- EN/CS flow;
- build hints.

### Phase 2: Derivux identity migration

Rename product/runtime identities consistently:

- `Clonux by Gravister` → `Derivux by Gravister`;
- plugin slug/API namespace/component IDs;
- PHP namespaces/classes;
- language namespaces;
- generated README/preview branding;
- route names and documentation.

This must be a semantic migration, not global search-and-replace without tests.

### Phase 3: architecture cleanup without feature creep

Refactor the controller into small responsibilities, for example:

- `ThemeDiscoveryService`;
- `ThemeInspectionService`;
- `DerivationPlanner`;
- `ThemeGenerator`;
- `GenerationTransaction` / cleanup boundary;
- preview service only if previews remain necessary.

The Admin2 controller should become thin orchestration around those services.

### Phase 4: correctness improvements

Before declaring Derivux functionally equivalent or better:

- resolve actual parent class/namespace instead of relying only on slug conversion;
- validate generated YAML/PHP metadata;
- add rollback/cleanup on failed generation;
- verify path confinement;
- test blueprint inheritance on current Grav 2/Admin2;
- determine whether copied parent configuration is desirable or unnecessarily sticky;
- verify which preview formats are actually required by current Admin2.

## Scope that remains deferred

Do not add during the migration unless explicitly approved:

- automatic activation of the generated theme;
- automatic cache clearing;
- automatic Tailwind/Vite/npm builds;
- template/CSS/JS override generation beyond the existing scaffold;
- parent-update synchronization;
- Elementux integration;
- Elementux Canvas-specific behavior;
- GitHub/GPM publishing of generated themes.

## Decision gate

The recovered source changes the recommended decision.

**Do not rewrite Clonux from scratch.** Preserve its proven inheritance-first behavior and Admin2 workflow, migrate it deliberately into Derivux, and improve the internals behind the same safe product contract.

The next implementation should therefore start from the supplied **Clonux 0.6.1 source as behavioral input**, while the current Derivux 0.1.0 repository remains the clean product baseline and governance home.
