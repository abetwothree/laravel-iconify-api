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
- `inline.defaults` now drives render options, not just plain SVG attributes. `width`,
  `height`, `rotate`, `flip`, the flip aliases and `inline` were silently swallowed when
  configured as defaults (only `color` happened to work), so `'width' => '2em'` still
  rendered `width="1em"`. Per-call options continue to override configured defaults.
- SVG element IDs no longer collide between icons on the same page. The ID counter is now
  a shared singleton, matching Iconify's module-scoped counter.
- Fractional icon dimensions are no longer truncated to whole numbers.
- A malformed icon set that resolves an icon to a zero `width` or `height` no longer
  raises an uncaught `DivisionByZeroError` (an HTTP 500 on the API routes). The icon is
  rendered with a 1:1 aspect ratio instead. No published icon set is affected.
- Aliases that override `body` or `hidden` are now honoured.
- `width` / `height` values of `0` or `""` are ignored and fall back to `1em`, matching
  Iconify's `mergeCustomisations`; the string `"0"` is kept, matching JavaScript
  truthiness.
- Passing `aria-hidden => null` now removes the default `aria-hidden="true"` attribute,
  matching Iconify's presence-based (rather than truthiness-based) handling of `null`;
  previously the explicit `null` was silently ignored and the default was kept.
- `aria-hidden` and `ariaHidden` now compose the way Iconify's single prop loop does:
  either key holding a value that is not `true` decides the attribute is removed.
  Previously `ariaHidden` overwrote an explicit `aria-hidden`, so
  `['aria-hidden' => false, 'ariaHidden' => true]` still emitted `aria-hidden="true"`.
- A `rotate` given as a fraction such as `1.5` no longer rotates the icon, wherever it
  comes from: the `rotate` option, an icon's own `rotate`, or an icon set's root
  `rotate`. PHP cast it to an int and rotated 90 degrees; JavaScript's `switch` matches
  no case and applies no rotation. Fractions are still added up first, so `0.5` on an
  icon plus `0.5` on its alias makes a whole rotation and does rotate 90 degrees, the
  same as upstream. Whole-number floats such as `2.0` still rotate.
- Framework-only props Iconify's React/Vue/Svelte components swallow — `icon`, `mode`,
  `ssr`, `onLoad`, `children`, `fallback`, `customise`, `_ref` — are no longer emitted as
  raw, invalid SVG attributes (for example `mode="mask"` used to leak through).
- Attribute names are validated, not just escaped. An option key that is not a valid XML
  name — such as `'x onload=alert(1)'` — is skipped instead of emitted; escaping alone did
  not help, because the key contains no HTML special character. This is a well-formedness
  check on the name only: a well-formed key such as `onclick` still renders as an
  attribute, same as any other Blade attribute bag.
- The `color` option rejects values that could inject an extra CSS declaration (containing
  `;`, `{`, `}`, or a CSS comment marker). Such a value is dropped entirely rather than
  emitted; legitimate values like `rgb(1,2,3)`, `hsl(210 100% 50%)`, `var(--x, red)`,
  `currentColor` and `color-mix(...)` are unaffected.
- An icon can no longer overwrite its icon set's cached metadata. Cache keys were a flat
  `{cache-prefix}:{set}:{name}`, so an icon named `info` — shipped by 70 of the 235 sets
  in `@iconify/json` — addressed the same key as the icon set info block. A single
  `GET /iconify/api/codicon.json?icons=info`, or a plain `icon('codicon:info')` render,
  permanently replaced the `/collection` and `/collections` payloads with icon body data;
  entries carry no TTL, so nothing healed it. Icon entries now live under an `icon:`
  segment and icon set metadata under a `meta:` segment.
- Icon names on the API routes are validated against Iconify's own `matchIconName`
  (`^[a-z0-9]+(-[a-z0-9]+)*$`) and counted into `not_found` when they do not match. All
  344,625 names in the 235 sets bundled with `@iconify/json` match it.
- A custom `IconFinder` that omits the optional `defaults` key is cached again. The
  staleness check read that key's presence, so such a finder got a 0% cache hit rate and
  re-read the icon set JSON on every request.

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
- **Cache keys changed shape.** Icons are now stored at
  `{cache-prefix}:{set}:icon:{shape-version}:{name}` and icon set metadata at
  `{cache-prefix}:{set}:meta:{info|summary|file:{type}}`. Every entry written by an
  earlier release is orphaned rather than migrated: the first request after deployment
  rebuilds what it needs, and the old entries expire with whatever policy the cache store
  applies to them (nothing, for a store without eviction — run `cache:clear`, or prune the
  `{cache-prefix}:` namespace, to reclaim the space). This also retires entries cached
  before the icon set root defaults were carried through, which would otherwise keep
  rendering with the wrong `viewBox`.
- `IconFinderContract::find()`'s `defaults` key is now optional rather than required. The
  shipped `IconFinder` and `IconFinderCached` still always return it — the icon set
  root's `left`, `top`, `width`, `height`, `rotate`, `hFlip` and `vFlip`, or `[]` when the
  set declares none — but a custom implementation that omits it now degrades gracefully
  to no icon-set root defaults (the pre-branch 16×16 fallback for a set that would
  otherwise supply them) instead of erroring at render time.

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
