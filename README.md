# Laravel Iconify API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abetwothree/laravel-iconify-api.svg?style=flat-square)](https://packagist.org/packages/abetwothree/laravel-iconify-api)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abetwothree/laravel-iconify-api/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abetwothree/laravel-iconify-api/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abetwothree/laravel-iconify-api/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abetwothree/laravel-iconify-api/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abetwothree/laravel-iconify-api.svg?style=flat-square)](https://packagist.org/packages/abetwothree/laravel-iconify-api)

Make your Laravel Application an API for on demand icons using the [Iconify](https://iconify.design/index.html) icon web components.

This Laravel package creates a few API routes for the [Iconify](https://iconify.design/index.html) icons [on demand API](https://iconify.design/docs/icon-components/). It allows you to easily use on demand icons and use your Laravel applicatioin as the Iconify API.

It works similarly to the [Node Iconify API](https://github.com/iconify/api) and is a spiritual successor to their [PHP implementation](https://github.com/iconify/api.php).

On demand icons work great whether you use Livewire, Inertia, or just plain Blade views to render your Laravel application and want to render icons dynamically using a single component.

## Requirements

- PHP 8.4
- Laravel 11.x

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

### How To Display Icons

To displays icons follow the instructions on the [Iconify](https://iconify.design/docs/icon-components/) on demand docs and use any of their component libraries in your Laravel Application.

You also need icon set data to be available in your application. You'll need to install the icon set data using NPM. See more [info here](https://iconify.design/docs/icons/icon-data.html#sources).

We recommend installing individual icon sets instead of the entire Iconify JSON set to keep your application lightweight. However, you can install the entire set if you wish and this package will work with either approach.

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
