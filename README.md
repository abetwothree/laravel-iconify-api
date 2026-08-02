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

By default Icon API routes will work out of the following route path in your Laravel application:

```
/iconify/api
```

The following routes are currently available:

- `/iconify/api/{prefix}.json?icons={icon-prefix}` - Returns icon SVG data for an icon set. Icon prefix can be comma separated for multiple icons.
- `/iconify/api/{prefix}/icons.json?icons={icon-prefix}` - Same as above.
- `/iconify/api/collections` - Returns a list of icon collections available in your application.
- `/iconify/api/collection?prefix={prefix}` - Returns the information for a specific icon collection.

### How To Display Dynamic On-Demand Icons

To display on-demand icons follow the instructions on the [Iconify](https://iconify.design/docs/icon-components/) on demand docs and use any of their component libraries in your Laravel Application.

You also need icon set data to be available in your application. You'll need to install the icon set data using NPM. See more [info here](https://iconify.design/docs/icons/icon-data.html#sources).

It is recommended to install individual icon sets instead of the entire Iconify JSON set to keep your application lightweight. However, you can install the entire set if you wish and this package will work with either approach.

## Real-Time Inline Icon Rendering

In addition to the HTTP API, this package can render icons directly to SVG in PHP for places where you want immediate server-side output.

The icon finding and caching goes through the same process as the HTTP API, ensuring consistent behavior and performance.

### Helper function

Use the global helper to render an icon string:

```php
$svg = icon('heroicons:clock');
```

Apply SVG attributes:

```php
$svg = icon('heroicons:clock', [
	'class' => 'w-6 h-6',
	'data-slot' => 'icon',
]);
```

### Blade component

Use the Blade component for direct rendering in views:

```blade
<x-icon name="heroicons:clock" />
<x-icon name="heroicons:clock" class="w-6 h-6" />
<x-icon name="heroicons:clock" data-slot="icon" />
```

### Supported options

Both the helper and the Blade component accept the same options as the official Iconify
components. Anything not listed here is passed through as a plain SVG attribute, except
`viewBox`, which is always computed from the icon data, and option keys that are not
well-formed XML attribute names, which are skipped.

That last rule is a well-formedness check on the *name* only. A key such as
`'x onload=alert(1)'` contains no HTML special character, so escaping alone would not
stop it opening a second, live attribute; it is dropped instead. A well-formed key such
as `onclick` still renders as an attribute, the same as it would through any Blade
attribute bag.

| Option | Values | Effect |
| --- | --- | --- |
| `width`, `height` | number, CSS length, `auto`, `unset` | Icon size. Defaults to `1em`. One side is derived from the other by aspect ratio. `unset`, `undefined` and `none` omit both attributes entirely. A falsy value (`0`, `''`, `false`) is dropped and falls back to `1em`; the *string* `'0'` is kept, matching JavaScript truthiness. |
| `color` | any CSS color | Applied via `style="color: …"`, matching React's `style.color = value`. Only affects monotone icons (paths using `fill="currentColor"` / `stroke="currentColor"`). This used to be emitted as a `color="…"` presentation attribute, which a CSS rule matching the `<svg>` could override; as an inline style it now beats any non-`!important` rule. If the value contains `;`, `{`, `}`, or a CSS comment marker (`/*` or `*/`) — which could inject an extra CSS declaration — it is dropped entirely and no `color` style is emitted at all. Values such as `rgb(1,2,3)`, `hsl(210 100% 50%)`, `var(--x, red)`, `currentColor` and `color-mix(...)` are unaffected. |
| `inline` | `true` | Adds `vertical-align: -0.125em` so the icon sits on the text baseline. |
| `rotate` | `1`–`3`, `"90deg"`, `"25%"` | Quarter-turn rotation. Non-quarter values are ignored. |
| `flip` | `"horizontal"`, `"vertical"`, `"horizontal,vertical"` | Flip shorthand. |
| `hFlip`, `vFlip` | `true` | Flip on one axis. |
| `h-flip`, `horizontal-flip`, `horizontalFlip` | `true` | Aliases for `hFlip`. |
| `v-flip`, `vertical-flip`, `verticalFlip` | `true` | Aliases for `vFlip`. |
| `aria-hidden` | anything other than `true` | Removes the default `aria-hidden="true"`. |

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

If your app already has a global helper or component with the same name, this package will skip registration and leave existing behavior untouched.

You can also customize or disable each one in `config/iconify-api.php`:

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

	'helper' => [
		'enabled' => true,
		'name' => 'icon',
	],

	'component' => [
		'enabled' => true,
		'name' => 'icon',
	],
],
```

Values from `defaults` are applied to every rendered icon. Per-icon options (helper or Blade attributes) override matching keys, except `class`, which is merged. Render options such as `width`, `height`, `rotate`, `flip`, `inline` and `color` are honoured here too, not just plain SVG attributes.

### PHPStan support

This package ships a PHPStan stub for the default helper name `icon`, so static analysis can recognize `icon()` calls out of the box.

If you rename the helper function (for example to `iconify_svg`), add a small project-level stub so PHPStan can recognize the custom function name:

```php
<?php

if (! function_exists('iconify_svg')) {
	/**
	 * @param  array<string, mixed>  $options
	 */
	function iconify_svg(string $name, array $options = []): string {}
}
```

Then include that stub in your `phpstan.neon`:

```neon
parameters:
	stubFiles:
		- stubs/icon-helper.stub.php
```

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

This package uses Laravel's caching system to cache the icon data to make repeated requests for the same icon faster. It caches icon data when it is requested so that it only caches the icons that are actually used in your application.

You can set which cache store to use for this package in your `config/iconify-api.php` file. Otherwise, it will use your default cache store setting.

A found icon is cached without an expiry — it cannot change while the installed version of the icon set stays the same. Nothing keys off the icon set file, so upgrading an icon package invalidates nothing: run `php artisan cache:clear` after `npm update @iconify/json` or after upgrading an `@iconify-json/*` package, or a redrawn icon keeps serving its old body indefinitely, on both the API routes and `icon('set:name')`.

A "this icon does not exist" result is cached only briefly, because any name a caller invents produces one; `not_found_cache_ttl` controls how long (300 seconds by default, `0` to skip caching misses entirely). The API routes also bound how many names one request may ask for, via `max_icons_per_request` (200 by default, `0` for no limit); a request above the limit is rejected with a `400`.

Cache keys are `{cache_key_prefix}:{icon-set-prefix}:icon:{shape-version}:{icon-name}` for icons and `{cache_key_prefix}:{icon-set-prefix}:meta:…` for icon set metadata. Icon names are not filtered, so a name a cache key cannot hold — one carrying a space, a `:`, a `/`, a control character or any other byte outside printable ASCII (so a non-ASCII name always hashes), or longer than 128 bytes — is replaced in that last segment by `h:` and a SHA-256 of the whole name; no icon set published through `@iconify/json` contains such a name, but a hand-authored one reached through a custom `icons_location` may, and its keys will not be readable back to a name. The shape version changes when the cached array shape does, which orphans the older entries rather than migrating them — run `php artisan cache:clear` after upgrading this package too, if you want the space back straight away.

## Missing Features

The MVP of this package was to provide an API for on demand icons in your Laravel Application. A few API endpoints that currently exist on the Node JS package that are missing in this package and will be added in future releases:

- [ ] Return icon data in in JSONP callback format.
- [ ] List icons in a collection.
- [ ] List icons categorized in a collection.
- [ ] Search endpoint for icons.
- [ ] Keywords endpoint for icons.

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
