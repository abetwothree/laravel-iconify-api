---
name: rendering-iconify-icons
description: >
    Use when adding, changing or debugging icons in a Laravel app that has abetwothree/laravel-iconify-api
    installed: Blade `<x-icon>` or `icon()`, the `@iconify` directive, Iconify's Vue, React or web components, or
    `@iconify-json/*` icon sets. Also use when an icon renders blank or stale, a page returns 500 with "Could not
    find the icons file for the set", icons must not load from api.iconify.design, a CSP blocks icons, a deploy
    drops node_modules, or PHP reports "Cannot redeclare function icon()".
compatibility: abetwothree/laravel-iconify-api (PHP 8.4+, Laravel 11, 12 or 13).
---

# Iconify icons in Laravel

`abetwothree/laravel-iconify-api` renders Iconify icons from one set of data in two ways: as inline SVG on the
server (`<x-icon>`, `icon()`), and as an Iconify API at `/iconify/api` that Iconify's on-demand JS components
call instead of `api.iconify.design`. Both read the icon set's JSON file from disk at runtime, on every cache
miss: `{icons_location}/@iconify-json/{prefix}/icons.json` first, then
`{icons_location}/@iconify/json/json/{prefix}.json`. `icons_location` defaults to `base_path('node_modules')`.
An icon works only where its set file exists.

## Pick the entry point

| Rendering from | Use |
| --- | --- |
| A Blade view | `<x-icon name="heroicons:clock" class="size-4" />` |
| Blade, as a string | `{!! icon('heroicons:clock', ['class' => 'size-4']) !!}`. Print with `{!! !!}`: `{{ }}` escapes the SVG. |
| PHP code | The facade `AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi::icon($name, $options)`, or `use function AbeTwoThree\LaravelIconifyApi\icon;` |
| Vue, React, Svelte or a web component | Iconify's component (`npm install @iconify/vue`, then `<Icon icon="mdi:github" />`), plus `@iconify` in the root Blade layout. See [On-demand JS components](#on-demand-js-components). |

`<x-icon>` and `icon()` render on the server. They need no JavaScript and no `@iconify`.

Names are `prefix:name`. In Heroicons v2 the bare name is the 24px outline (`heroicons:clock`); filled
variants end in `-solid`, `-20-solid` or `-16-solid`. Every rendered `<svg>` carries
`class="iconify iconify--{provider} iconify--{prefix}"` with your own `class` merged in.

## Install one icon set per prefix

```bash
npm install @iconify-json/mdi
```

- Install every prefix the app uses, including prefixes used only by JS components. The browser fetches icons
  from `/iconify/api`, and that endpoint reads the set on the server. The JS bundle never imports the set.
- Keep sets in `dependencies`, which is where a plain `npm install` puts them. They are runtime data for PHP,
  and a production prune removes `devDependencies`.
- A single set is under a few MB. The full `@iconify/json` (every set, about 400 MB) also works; keep it only
  if the app already uses it.
- `GET /iconify/api/collections` lists the installed sets.

## What a missing icon does

| Situation | `icon()` / facade | `<x-icon>` | `GET /iconify/api/{prefix}.json?icons=…` |
| --- | --- | --- | --- |
| Name not in an installed set | `''` | renders nothing | 200, name listed under `not_found` |
| Malformed name (no `prefix:`) | `''` | renders nothing | n/a |
| Set not installed | throws `Could not find the icons file for the set: {prefix}` | 500 page (`ViewException`) | 500 |

A blank icon means a wrong name. A 500 that names a set means that set is not installed: run
`npm install @iconify-json/{prefix}`. The failed lookup is not cached, so the icon renders on the next request
without a `cache:clear`. An icon in a shared layout takes every page down with it.

## Options

Pass options as Blade attributes or in the `$options` array. Any other key becomes an SVG attribute.

| Option | Effect |
| --- | --- |
| `width`, `height` | Height defaults to `1em` and width follows the aspect ratio. A `size-*` or `w-* h-*` class overrides both. |
| `color` | Emits `style="color: …"`. It affects only icons drawn with `currentColor`, as are most monotone sets, and so does a text colour class. |
| `inline` | Adds `vertical-align: -0.125em`, so the icon sits on the text baseline. |
| `rotate` | Quarter turns: `rotate="90deg"`, or an integer count. |
| `flip`, `h-flip`, `v-flip` | `flip="horizontal"`, or `h-flip="true"` to mirror. |
| `aria-hidden` | Icons get `aria-hidden="true"` by default. Pass `aria-hidden="false"` and an `aria-label` for an icon that carries meaning. |

- Boolean options accept only `true`, `"true"` and integer `1`. Bare `inline`, `h-flip="true"` and
  `:h-flip="$order->isShipped()"` work. `inline="1"` passes the string `"1"`, which silently does nothing.
- `inline` needs an inline formatting context. Tailwind's preflight sets every `svg` to `display: block`, and a
  flex parent ignores `vertical-align`. Inside running text, add `class="inline-block"`. Inside an
  `inline-flex items-center` row, leave `inline` off.
- App-wide defaults go in `inline.defaults` in `config/iconify-api.php`
  (`php artisan vendor:publish --tag=iconify-api-config`).

## On-demand JS components

Put `@iconify` in the root Blade layout's `<head>`, before `@vite`:

```blade
@iconify
@vite(['resources/css/app.css', 'resources/js/app.ts'])
```

`@iconify` prints an inline script that sets `window.IconifyProviders`, pointing Iconify's default provider at
this app's relative `/iconify/api`. Iconify reads that global once, when its module loads. Without the
directive, or with it after `@vite`, the components fall back to `api.iconify.design` and still render, so
nothing looks broken.

The built bundle keeps the string `api.iconify.design` as Iconify's default, so finding it in `public/build`
proves nothing. To check, confirm that the page HTML has the `IconifyProviders` script before the module
scripts, and that `/iconify/api/{prefix}.json?icons=a,b` returns both icons with nothing under `not_found`.

## Content Security Policy

`@iconify` adds the nonce from `Vite::cspNonce()` by itself. An app that calls `Vite::useCspNonce()`, directly
or through spatie/laravel-csp, needs nothing more. For a nonce from anywhere else, pass it: `@iconify($nonce)`.
A missing nonce shows up as an inline script refused by `script-src`, followed by a refused connection to
`api.iconify.design`. After upgrading this package, run `php artisan view:clear` once.

## Caching

Found icons stay in Laravel's cache (`cache_store`) with no expiry. Misses expire after `not_found_cache_ttl`
(300 s). Nothing watches the set files, so run `php artisan cache:clear` after `npm update` of a set, after
upgrading an `@iconify-json/*` package, and after upgrading this package. A hard refresh and `view:clear`
leave the old icon in place.

## Production deploys

The set files must exist on the server at runtime. A deploy that builds assets and then drops `node_modules`
returns 500 on every page with an `<x-icon>`. Both fixes keep icons self-hosted:

1. **Ship the production `node_modules`.** With the sets in `dependencies`, run
   `npm ci && npm run build && npm prune --omit=dev`, and deploy what remains. In a Docker multi-stage build,
   end the assets stage with `RUN npm prune --omit=dev` and add
   `COPY --from=assets /app/node_modules ./node_modules` to the final stage. Copying only
   `node_modules/@iconify-json` also works.
2. **Ship the JSON with the app.** Copy each set's folder into a committed directory, for example
   `node_modules/@iconify-json/mdi` to `resources/iconify/@iconify-json/mdi`, and set
   `'icons_location' => resource_path('iconify')` in `config/iconify-api.php`. Production then needs no npm.
   Refresh the copy when a set updates.

The cache does not replace the files: any icon not requested since the last `cache:clear` reads its set file.

## The app already has a global `icon()`

Composer loads the package's `icon()` before the app's own helper files. An unguarded app `icon()` fails with
`Cannot redeclare function icon()`. A `function_exists` guard stops the error but sends every existing call to
the package's `icon(string $name, array $options = [])`: `icon('box')` renders `''` and
`icon('user', 'text-gray-400')` throws a `TypeError`. Rename the app's helper, for example to `fa_icon()`, and
update its call sites. Code that must not depend on the global name can use the facade or the namespaced
function.

If the app already registers an `<x-icon>` Blade component, the package skips its own. Rename the package's
component with `inline.component.name`.

## Checks

```bash
php artisan tinker --execute="echo \AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi::icon('mdi:truck-fast');"
curl -s "http://127.0.0.1:8000/iconify/api/mdi.json?icons=truck-fast,github"
curl -s "http://127.0.0.1:8000/iconify/api/collections"
```

The first prints a non-empty `<svg …>`, or throws if the set is missing. The second lists both icons, with
nothing under `not_found`. The third lists the installed sets.
