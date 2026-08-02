# Changelog

All notable changes to `laravel-iconify-api` will be documented in this file.

## Unreleased

### Fixed

- Icons now render with the icon set's own coordinate system. Icon-set root `width`,
  `height`, `left`, `top`, `rotate`, `hFlip` and `vFlip` were previously ignored, so a
  24×24 icon set such as `heroicons` rendered with `viewBox="0 0 16 16"` and drew at the
  wrong scale.
- The icons API now returns the icon set root's `left`, `top` and `provider` alongside
  `width` and `height`, matching the `propsToCopy` list Iconify's `getIcons()` uses.
  Previously a set with a negative origin such as `jam` was served without its offsets,
  so a browser-side Iconify client rebuilt every icon as `viewBox="0 0 24 24"` instead
  of `viewBox="-2 -2 24 24"`.
- SVG element IDs no longer collide between icons on the same page. The ID counter is now
  a shared singleton, matching Iconify's module-scoped counter.
- Fractional icon dimensions are no longer truncated to whole numbers.
- Aliases that override `body` or `hidden` are now honoured.
- `width` / `height` values of `0` or `""` are ignored and fall back to `1em`, matching
  Iconify's `mergeCustomisations`; the string `"0"` is kept, matching JavaScript
  truthiness.
- Passing `aria-hidden => null` now removes the default `aria-hidden="true"` attribute,
  matching Iconify's presence-based (rather than truthiness-based) handling of `null`;
  previously the explicit `null` was silently ignored and the default was kept.
- Framework-only props Iconify's React/Vue/Svelte components swallow — `icon`, `mode`,
  `ssr`, `onLoad`/`onload`, `children`, `fallback`, `customise`, `_ref` — are no longer
  emitted as raw, invalid SVG attributes (for example `mode="mask"` used to leak through).
- The `color` option rejects values that could inject an extra CSS declaration (containing
  `;`, `{`, `}`, or a CSS comment marker). Such a value is dropped entirely rather than
  emitted; legitimate values like `rgb(1,2,3)`, `hsl(210 100% 50%)`, `var(--x, red)`,
  `currentColor` and `color-mix(...)` are unaffected.

### Added

- `inline` option, adding `vertical-align: -0.125em`.
- `color` option, folded into the `style` attribute.
- Flip aliases `h-flip`, `horizontal-flip`, `horizontalFlip`, `v-flip`, `vertical-flip`
  and `verticalFlip`.
- End-to-end parity vectors that diff the full resolution pipeline against upstream
  `getIconData()` + `iconToSVG()`.

### Changed

- `IconSvgRenderer::__construct()` no longer takes an `IconSetInfoFinder`. It was reading
  the icon set's `info` metadata block, which carries no `width` and only a
  sample-display `height`.
- Icons cached before this release are treated as stale and transparently refreshed. The
  cached icon set summary moved to a new cache key for the same reason, so a warm cache
  does not keep serving responses without the root `left`/`top`.

### Breaking

- **Custom `IconFinder` implementations must now return a `defaults` key.** Every entry
  returned from `IconFinderContract::find()` is required to carry a `defaults` array —
  the icon set root's `left`, `top`, `width`, `height`, `rotate`, `hFlip` and `vFlip`,
  or `[]` when the set declares none. The interface signature is unchanged, so this
  fails at runtime rather than at compile time: an implementation that omits the key
  raises an undefined-array-key warning and then a `TypeError`. If you have bound your
  own `IconFinderContract`, add the key. The shipped `IconFinder` and `IconFinderCached`
  already do.

## v1.2.0 - 2025-05-05

Fix finding icons by aliased parent component

**Full Changelog**: https://github.com/abetwothree/laravel-iconify-api/compare/v1.1.0...v1.2.0

## v1.1.0 - 2025-02-25

Support Laravel 12

**Full Changelog**: https://github.com/abetwothree/laravel-iconify-api/compare/v1.0.1...v1.1.0

## v1.0.1 - 2025-02-11

- Adds more documentation
- Bugfix when an icon set doesn't have a width or height and remove that from the response

### What's Changed

* Add further documentation and fix bug for missing width and height of icon set by @abetwothree in https://github.com/abetwothree/laravel-iconify-api/pull/2

**Full Changelog**: https://github.com/abetwothree/laravel-iconify-api/compare/v1.0.0...v1.0.1

## v1.0.0 release - 2025-02-10

This is the initial release of the package

It creates the following API routes:

- Routes for displaying icons for icon specific icon sets
- Collection route to display all icon sets
- Collection icon set to display info for specific icon set
