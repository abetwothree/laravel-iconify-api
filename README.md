# Laravel Iconify API & Icon Rendering

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abetwothree/laravel-iconify-api.svg?style=flat-square)](https://packagist.org/packages/abetwothree/laravel-iconify-api)
[![Laravel Compatibility](https://badge.laravel.cloud/badge/abetwothree/laravel-iconify-api)](https://packagist.org/packages/abetwothree/laravel-iconify-api)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abetwothree/laravel-iconify-api/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abetwothree/laravel-iconify-api/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abetwothree/laravel-iconify-api/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abetwothree/laravel-iconify-api/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abetwothree/laravel-iconify-api.svg?style=flat-square)](https://packagist.org/packages/abetwothree/laravel-iconify-api)

Make your Laravel Application an API for on demand icons using the [Iconify](https://iconify.design/index.html) icon web components.

This Laravel package creates a few API routes for the [Iconify](https://iconify.design/index.html) icons [on demand API](https://iconify.design/docs/icon-components/). It allows you to easily use on demand icons and use your Laravel applicatioin as the Iconify API.

It works similarly to the [Node Iconify API](https://github.com/iconify/api) and is a spiritual successor to their [PHP implementation](https://github.com/iconify/api.php).

On demand icons work great whether you use Livewire, Inertia, or just plain Blade views to render your Laravel application and want to render icons dynamically using a single component.

Additionally, this package provides a convenient way to render Iconify icons inline as SVGs within PHP files or in your Blade views.

## Also By Me

- [Laravel TypeScript Publish Package](https://github.com/abetwothree/laravel-ts-publish)
- [Tolki JS NPM packages](https://github.com/abetwothree/tolki)

## Requirements

- PHP 8.5, 8.4
- Laravel 13.x, 12.x or 11.x

## How To Use

Install the package via composer:

```bash
composer require abetwothree/laravel-iconify-api
```

In your core application blade layout file add the following directive in the head section before your application's JS bundle:

```html
@iconify
```

This will configure the [Iconify API](https://iconify.design/docs/api/providers.html#api-config) on demand icons to load the icons from your Laravel application instead of the Iconify API.

If your application sends a Content Security Policy, see [Content Security Policy](#content-security-policy).

By default Icon API routes will work out of the following route path in your Laravel application:

```
/iconify/api
```

The following routes are currently available:

- `/iconify/api/{prefix}.json?icons={icon-prefix}` - Returns icon SVG data for an icon set. Icon prefix can be comma separated for multiple icons.
- `/iconify/api/{prefix}/icons.json?icons={icon-prefix}` - Same as above.
- `/iconify/api/collections` - Returns a list of icon collections available in your application.
- `/iconify/api/collection?prefix={prefix}` - Returns the information for a specific icon collection.

### Error responses

The `icons` parameter must be a single comma separated string. A request that sends it as an array — `?icons[]=home&icons[]=account` — is rejected with a `400` rather than being flattened into a nonsense lookup.

The `prefix` parameter on `/iconify/api/collection` is bound by the same rule: a missing prefix is a `404`, and a prefix sent as an array is a `400`.

### How To Display Dynamic On-Demand Icons

To display on-demand icons follow the instructions on the [Iconify](https://iconify.design/docs/icon-components/) on demand docs and use any of their component libraries in your Laravel Application.

You also need icon set data to be available in your application. You'll need to install the icon set data using NPM. See more [info here](https://iconify.design/docs/icons/icon-data.html#sources).

It is recommended to install individual icon sets instead of the entire Iconify JSON set to keep your application lightweight. However, you can install the entire set if you wish and this package will work with either approach.

### Content Security Policy

`@iconify` prints an inline `<script>` tag. A nonce-based Content Security Policy (`script-src 'nonce-…'`) blocks that tag unless it includes the nonce. The blocked script never sets `window.IconifyProviders`, so Iconify's components fall back to the public Iconify API. A strict `connect-src` blocks that request too, and no icons load.

If your app creates its nonce with `Vite::useCspNonce()`, directly or through a package such as [spatie/laravel-csp](https://github.com/spatie/laravel-csp#integration-with-vite), `@iconify` reads that nonce and adds it to the tag, the same way `@vite` does. You don't need to configure anything.

If your nonce comes from somewhere else, pass it to the directive. `@iconify` uses a nonce passed this way instead of the Vite one:

```blade
@iconify($nonce)
```

`@iconify` builds the tag on every render, so each request gets its own nonce.

After upgrading, run `php artisan view:clear` once. Blade recompiles a view only when its source file changes, so views compiled by an earlier version of this package still contain the old tag without a nonce.

If you added the nonce to the `@iconify` output yourself, for example by running `str_replace()` on what `IconifyDirective::render()` returns, remove that code. The tag now includes the nonce, so your code would add a second `nonce` attribute.

## Real-Time Inline Icon Rendering

In addition to the HTTP API, this package can render icons directly to SVG in PHP for places where you want immediate server-side output.

The icon finding and caching goes through the same process as the HTTP API, ensuring consistent behavior and performance.

### Helper functions and facade

There are three ways to render an icon string. The global helper:

```php
$svg = icon('heroicons:clock');
```

The namespaced function, which shouldn't clash with another `icon()` global method:

```php
use function AbeTwoThree\LaravelIconifyApi\icon;

$svg = icon('heroicons:clock');
```

The facade:

```php
use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

$svg = LaravelIconifyApi::icon('heroicons:clock');
```

In Blade:

```blade
{!! icon('heroicons:clock') !!}

@use('function AbeTwoThree\LaravelIconifyApi\icon')
{!! icon('heroicons:clock') !!}

{!! LaravelIconifyApi::icon('heroicons:clock') !!}
```

`@use('function …')` needs Laravel 12.11 or later. All three entry points return a raw SVG string, so Blade must print it with `{!! !!}`. `{{ }}` would escape the markup.

They are plain autoloaded code, so PHPStan and your IDE find them without stubs.

Apply SVG attributes:

```php
$svg = icon('heroicons:clock', [
	'class' => 'w-6 h-6',
	'data-slot' => 'icon',
]);
```

### Using a different helper name

Earlier versions renamed the helper through config. That is gone, because a function declared at boot is invisible to static analysis, and declaring it meant writing a file on every boot.

To get a different name, write a thin wrapper in your app:

```php
<?php

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

if (! function_exists('iconify_svg')) {
	/**
	 * @param  array<string, mixed>  $options
	 */
	function iconify_svg(string $name, array $options = []): string
	{
		return LaravelIconifyApi::icon($name, $options);
	}
}
```

Then autoload the file from `composer.json`:

```json
"autoload": {
	"files": [
		"app/helpers.php"
	]
}
```

and run `composer dump-autoload`.

Don't name the wrapper `icon`. See [Naming and collision safety](#naming-and-collision-safety) for why.

### Blade component

Use the Blade component for direct rendering in views:

```blade
<x-icon name="heroicons:clock" />
<x-icon name="heroicons:clock" class="w-6 h-6" />
<x-icon name="heroicons:clock" data-slot="icon" />
```

### Supported options

The helpers, the facade and the Blade component take the same options. Anything not listed below becomes an SVG attribute, so `onclick` and bindings like `@click`, `x-on:click` or `wire:model` render as written.

Keys that are not well-formed attribute names are skipped, which stops a key like `'x onload=alert(1)'` from opening a second live attribute. The check reads the name only, so it is not an XSS filter. Values that are not a string, number, boolean or `__toString()` object are skipped too, and `null`, `false` and `''` produce no attribute rather than an empty one. Boolean `true` renders as `"1"`.

Every icon carries `class="iconify iconify--{provider} iconify--{prefix}"`, with your own `class` merged onto it. `viewBox` is always computed from the icon data.

| Option | Values | Effect |
| --- | --- | --- |
| `width`, `height` | number, CSS length, `auto`, `unset` | Set one and the other follows the aspect ratio. Height defaults to `1em`, so a non-square icon gets a computed width. `auto` uses the icon's own viewBox size. `unset`, `undefined` and `none` omit the attribute, and take the other side with them unless you set it. |
| `color` | any CSS color | Emitted as `style="color: …"`, so it only affects monotone icons, the ones drawn with `currentColor`. A value containing `;`, `{`, `}`, `/*` or `*/` is dropped silently with no fallback. `color-mix(…)` and other functions are fine. |
| `inline` | `true` | Adds `vertical-align: -0.125em` so the icon sits on the text baseline. |
| `rotate` | any integer, `"90deg"`, `"25%"` | Quarter turns, counted mod 4. Units are case-sensitive and untrimmed; anything that misses a whole quarter turn is ignored. |
| `flip` | `"horizontal"`, `"vertical"`, `"horizontal,vertical"` | Setting both is a 180° rotation, and it adds to `rotate`. |
| `hFlip`, `vFlip` | `true` | One axis each. Aliases: `h-flip`, `horizontal-flip`, `horizontalFlip`, and the `v-` equivalents. |
| `aria-hidden` | anything but `true` | Removes the default `aria-hidden="true"`. Also spelled `ariaHidden`. |

Boolean options accept only `true`, `"true"` and integer `1`. Blade passes every attribute as a string, so `<x-icon inline />` and `h-flip="true"` work while `inline="1"` silently does nothing.

A missing or malformed icon name renders an empty string, with no exception and no log entry.

```blade
<x-icon name="heroicons:clock" width="32" color="rebeccapurple" inline />
<x-icon name="heroicons:clock" rotate="90deg" h-flip="true" />
```

```php
$svg = icon('heroicons:clock', ['width' => 32, 'color' => 'rebeccapurple', 'inline' => true]);
```

A `style` you supply yourself is always emitted last, so it overrides the `color` and
`inline` styles the package generates.

The framework-only props Iconify's React/Vue/Svelte components accept — `icon`, `mode`,
`ssr`, `onLoad`, `children`, `fallback`, `customise`, `_ref` — are accepted and ignored
rather than emitted as attributes. Alternate render modes (`mode="bg"`, `mode="mask"`)
are not implemented; icons always render as inline `<svg>`.

### Naming and collision safety

Composer loads every dependency's `files` before your app's own, so the package's global `icon()` exists first.

- A guarded `icon()` in your app (wrapped in `if (! function_exists('icon'))`) is skipped silently.
- An unguarded one fails with `Cannot redeclare function icon()`.

To keep your own `icon()`, pick one of these:

- Name your function something else. See [Using a different helper name](#using-a-different-helper-name).
- Call the namespaced function or the facade, which never collide.
- Require your own `icon()` before `vendor/autoload.php` in `public/index.php`, `artisan` and your PHPUnit bootstrap. The package's declaration then sees yours and steps aside. This does not cover Laravel Octane, whose worker loads `vendor/autoload.php` itself. Under Octane, use another name or the namespaced function.

If another package also declares a global `icon()`, whichever Composer loads first wins, but only when that package guards its declaration with `function_exists`. An unguarded one fails with `Cannot redeclare function icon()`. The namespaced function and the facade are unambiguous.

If your app already has a Blade component with the same name, this package skips registering its own and leaves existing behavior untouched.

You can rename or disable the Blade component, and set rendering defaults, in `config/iconify-api.php`:

```php
'inline' => [
	'enabled' => true,

	'defaults' => [
		'class' => '',
		// Any default SVG attribute or render option is supported.
		// Examples:
		// 'data-source' => 'iconify-api',
		// 'style' => 'vertical-align: middle;',
		// 'width' => '1.5em',
		// 'inline' => true,
	],

	'component' => [
		'enabled' => true,
		'name' => 'icon',
	],
],
```

`inline.enabled` and `inline.component` only affect the Blade component. `defaults` apply to every entry point.

Values from `defaults` are applied to every rendered icon. Per-icon options (helper or Blade attributes) override matching keys, except `class`, which is merged. Render options such as `width`, `height`, `rotate`, `flip`, `inline` and `color` are honoured here too, not just plain SVG attributes.

## Untrusted Icon Sets

The package renders and serves icon bodies exactly as the set file has them, through the API, `icon()`, `<x-icon>` and the facade. Iconify itself assumes bodies were validated, so a body from a set you do not fully trust can carry script.

Check a body with `IconBody::isSafe()`, which returns a `bool`:

```php
use AbeTwoThree\LaravelIconifyApi\Icons\IconBody;

if (! IconBody::isSafe($body)) {
    // Skip or report the icon.
}
```

It rejects a body that contains any of these:

- A `<script>` element.
- Any `on*` event attribute, such as `onload` or `onbegin`.
- Any `href` or `xlink:href` that does not start with `#` or `data:image/`. The value is read after decoding HTML entities, so `&#106;avascript:` is caught.
- A `<foreignObject>`, `<iframe>`, `<embed>` or `<object>` element.
- SMIL animation whose `attributeName` targets `href`.

It accepts `#id` references from gradients, masks and `<use>`, `data:image/` hrefs, and SMIL animation of other attributes. Every icon in `@iconify/json` passes.

The check never modifies a body. It rejects known script vectors, but it is not an HTML sanitizer.

## Advanced Configuration

To configure the package, you can publish the config file using the following command:

```bash
php artisan vendor:publish --tag="iconify-api-config"
```

This will publish a `iconify-api.php` file in your `config` directory. You can then configure the package to your liking.

For advanced setting details, please see the [config file](config/iconify-api.php).

If you update your configuration file, make sure to break your application cache with the following commands:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Icon Caching

This package caches icon data through Laravel's cache as icons are requested, so only the icons your application uses get stored. Set `cache_store` in `config/iconify-api.php` to pick a store, or leave it unset to use your default.

Found icons never expire, since an icon cannot change while the installed icon set stays the same. Nothing watches the icon set files, so run `php artisan cache:clear` after `npm update @iconify/json`, after upgrading an `@iconify-json/*` package, or after upgrading this package. Skip it and a redrawn icon keeps serving its old body.

Misses expire after 300 seconds, since any name a caller invents produces one. Change that with `not_found_cache_ttl`, or set it to `0` to skip caching misses. The API routes also cap how many icons one request may ask for with `max_icons_per_request` (200 by default, `0` for no limit); anything over the cap gets a `400`.

Cache keys look like `{cache_key_prefix}:{icon-set-prefix}:icon:{shape-version}:{icon-name}`, with `meta:` in place of `icon:` for icon set metadata. A name that a cache key cannot hold, one carrying a space or running past 128 bytes, is replaced by a SHA-256 hash. No published icon set has such a name.

## Missing Features

The MVP of this package was to provide an API for on demand icons in your Laravel Application. A few API endpoints that currently exist on the Node JS package that are missing in this package and will be added in future releases:

- [ ] Return icon data in in JSONP callback format.
- [ ] List icons in a collection.
- [ ] List icons categorized in a collection.
- [ ] Search endpoint for icons.
- [ ] Keywords endpoint for icons.

## Upgrading to 1.6

Entries are ordered by how many apps they affect.

### The `inline.helper` config is removed

Detection: your `config/iconify-api.php` still has an `inline.helper` block. Version 1.6 does not act on it, except to warn at boot. If you renamed the helper and nothing else declares that name, boot throws:

```
The iconify-api.inline.helper config was removed in 1.6, so iconify_svg() is no longer declared. Declare iconify_svg() yourself or call icon(), then remove inline.helper from config/iconify-api.php. See https://github.com/abetwothree/laravel-iconify-api#using-a-different-helper-name
```

Every other leftover `inline.helper` block (the defaults, a disabled helper, inline rendering turned off with `inline.enabled => false`, a name that is not a valid PHP function name, or a name something else declares) boots normally and logs this `E_USER_DEPRECATED`. Laravel sends it to the `deprecations` log channel, which is off by default: set `LOG_DEPRECATIONS_CHANNEL` (for example `stack`) to see it:

```
The iconify-api.inline.helper config was removed in 1.6 and is ignored; icon() is always declared. Remove inline.helper from config/iconify-api.php. See https://github.com/abetwothree/laravel-iconify-api#upgrading-to-16
```

If your config is cached, delete `bootstrap/cache/config.php` first, because every artisan command (including `config:clear`) boots the package first and would hit the exception again.

If you renamed the helper, write the wrapper from [Using a different helper name](#using-a-different-helper-name). If you kept the defaults, delete the block.

### `inline.enabled = false` no longer turns off `icon()`

Before, `inline.enabled => false` removed the helper. Now `icon()`, the namespaced function and the facade are always available while the package is enabled (`iconify-api.enabled`, `ICONIFY_API_ENABLED`), and `inline.enabled` only controls the Blade component. To stop using the helpers, stop calling them.

### Your own global `icon()` now loses to the package's

Composer loads the package's `icon()` before your app's `files`. A guarded `icon()` in your app is skipped, and an unguarded one fails with `Cannot redeclare function icon()`. Rename it, call the namespaced function or the facade, or require your own before `vendor/autoload.php`. See [Naming and collision safety](#naming-and-collision-safety).

### PHPStan

If you use `phpstan/extension-installer`, do nothing. If your `phpstan.neon` includes `vendor/abetwothree/laravel-iconify-api/extension.neon` by hand, delete that include, because the file is gone and PHPStan fails on it. Also delete any stub you added for `icon()` or for a renamed helper. The functions are autoloaded now, so PHPStan finds them.

### New entry points

`AbeTwoThree\LaravelIconifyApi\icon()` and `LaravelIconifyApi::icon()` are new. Both return the same SVG as the global `icon()`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Code Quality

This package uses the following code quality tools:

- PHPStan 2.x at level 10 for static analysis.
- Laravel Pint for consistent code style.
- PHP Pest for testing.

## Credits

- [Abraham Arango](https://github.com/abetwothree)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
