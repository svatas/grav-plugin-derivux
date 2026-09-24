# Clonux → Derivux audit

> Date: 2026-09-25  
> Status: architecture/input audit only; no Clonux implementation was migrated.

## Executive finding

There is no verified Clonux source implementation available in the current GitHub repository history or connected project material that can be mechanically renamed or ported into Derivux.

The repository now named `svatas/grav-plugin-derivux` was empty before the Derivux bootstrap. Searches for a separate `Clonux by Gravister` repository/source did not produce an implementation to inspect. Therefore any claim that Derivux 0.1.0 is renamed Clonux code would be false.

Clonux remains useful as **product-history input**: its intent was safe cloning/migration workflows around Grav themes. Derivux deliberately narrows and modernizes that idea around native Grav theme inheritance.

## What is retained conceptually

The following ideas are worth preserving as product intent, not as source compatibility requirements:

- a guided workflow rather than asking users to hand-build derived themes;
- explicit source/parent theme selection;
- explicit target identity;
- preflight checks before filesystem mutation;
- non-destructive behavior by default;
- clear reporting of what will be created or changed;
- failure should leave the existing site/theme state intact.

## What is deliberately not retained

- Full parent-theme copying as the default derivation mechanism.
- Compatibility obligations to an unverified historical Clonux implementation.
- Opaque "clone everything and edit later" behavior.
- Unnecessary parallel infrastructure where Grav already provides inheritance, configuration, streams or blueprints.
- Automatic coupling to Elementux or Elementux Canvas.

## Grav-native basis verified for Derivux

Current Grav 2 documentation explicitly describes theme inheritance as the preferred way to customize a theme: the derived theme contains the parts that differ, while the base theme handles the rest.

The documented manual inheritance model uses a theme configuration stream whose lookup order is the derived theme first and the parent theme second. A derived theme also has its own theme PHP class and blueprint/configuration metadata. Grav documentation further recommends avoiding copied templates when extension/inheritance can keep the installation receiving upstream fixes.

This matches the approved Derivux architecture: **derive minimally, inherit maximally**.

## Proposed first functional workflow

The first functional milestone should be deliberately small. Working name: **0.2.0 — Minimal Theme Derivation**.

### Input

1. Select one installed parent theme from `user/themes`.
2. Enter a new derived-theme slug.
3. Enter a human-readable title.
4. Enter optional author/metadata values that belong to the new theme rather than the parent.

### Preflight

Before writing anything, Derivux should validate at minimum:

- parent theme directory exists and is readable;
- parent has the metadata/configuration needed to act as a usable Grav theme;
- target slug is valid and safe as a directory/theme identifier;
- target directory does not already exist;
- target is not the same theme as the parent;
- required generated files can be determined before mutation;
- no generated path escapes `user/themes/<target>`.

The first release should **not overwrite** an existing target theme.

### Plan / impact preview

Before generation, show the exact target and generated file set. The MVP plan should make it obvious that Derivux is not cloning the parent tree.

Expected minimal generated set:

- `<target>.php`
- `<target>.yaml`
- `blueprints.yaml`
- `README.md`
- `CHANGELOG.md`
- `LICENSE`

Images such as `thumbnail.jpg` / `screenshot.jpg` should not be silently copied in the first MVP unless a later UX decision explicitly defines that behavior.

### Generation

Generation should create a **new directory only after preflight succeeds**. The generated `<target>.yaml` should establish the Grav theme stream lookup order with the derived theme before the selected parent theme.

The generated theme PHP class should derive from the selected parent theme class only when Derivux can resolve that relationship safely. Parent class/namespace discovery therefore belongs to MVP validation, not string guessing.

Parent templates, CSS, JS and other assets should **not** be copied by default. A user can add overrides later; selective override tooling is a later milestone.

### Post-generation validation

After creation Derivux should verify:

- all expected files exist;
- generated YAML parses;
- the inheritance stream points to the intended child and parent paths;
- PHP class/file identity is internally consistent;
- the target remains isolated under its expected theme directory.

If post-generation validation fails, the operation should remove only the newly created target directory from that operation and report the failure.

## Explicitly deferred beyond 0.2.0

- switching the site's active theme automatically;
- copying or generating arbitrary template overrides;
- diffing parent files;
- synchronizing a derived theme when the parent changes;
- importing/migrating historical Clonux projects;
- Elementux integration;
- Elementux Canvas-specific generation;
- packaging/publishing generated themes to GPM;
- repository creation for generated themes.

## Recommended implementation sequence

1. Parent-theme discovery and metadata inspection service.
2. Target identity and path validation.
3. Deterministic derivation plan object with no filesystem writes.
4. Minimal file generator.
5. Transaction-like create/validate/cleanup behavior for the new target directory.
6. Admin2 UI only after the derivation service works independently and can be tested without the UI.
7. Runtime test on a Grav 2 installation with at least one simple parent theme and one more complex theme.

## Decision gate

No 0.2.0 implementation should begin until the workflow and generated-file boundary above are explicitly accepted or amended. The key product question is not how much of Clonux to copy. There is no verified Clonux code to copy. The question is how little Derivux needs to generate while still producing a valid, maintainable inherited Grav theme.
