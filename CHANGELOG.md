# Changelog

## 0.6.2 - 2026-09-25

- Established the product identity as **Derivux by Gravister** with plugin slug `gravister-derivux`.
- Preserved the mature inherited-theme scaffolding workflow: source selection, target validation, dry-run review, creation report and post-creation navigation.
- Preserved parent blueprint inheritance and support for current API response envelopes.
- Aligned the Admin2 integration with the current Grav 2 / Gravister approach:
  - active locale through `window.__GRAV_I18N`;
  - API token/base/prefix supplied by Admin2;
  - configurable Admin route handling;
  - controller loading compatible with cached API route dispatchers.
- Added explicit `api.derivux.read` and `api.derivux.create` permissions.
- Added an explicit Admin2 dependency.
- Added safer parent-theme PHP class discovery instead of assuming the class name from the theme slug.
- Added transactional cleanup of a newly created target directory when generation fails.
- Kept the conservative safety model: no overwrite, no parent modification, no automatic activation, no cache clearing, no shell and no build execution.
- Added GitHub Actions static checks and README status badges.
- Removed unrelated placeholder page-template integration from the plugin package.
- Audited the code against Grav 2.2.0, Admin2 2.1.23 and API 1.0.40.
