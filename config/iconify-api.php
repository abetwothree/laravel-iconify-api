<?php

declare(strict_types=1);

return [
    'enabled' => env('ICONIFY_API_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Iconify API Domain
    |--------------------------------------------------------------------------
    | You may change the domain where Iconify API should be active.
    | If the domain is empty, it will be active on your application's domain.
    |
    */

    'route_domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Iconify API Route
    |--------------------------------------------------------------------------
    | Iconify API will be available under this URL path.
    |
    */

    'route_path' => 'iconify',

    /*
    |--------------------------------------------------------------------------
    | Iconify API middleware.
    |--------------------------------------------------------------------------
    | Optional middleware to use on every API request.
    |
    */

    'api_middleware' => [
        'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Iconify Icons Sets Location
    |--------------------------------------------------------------------------
    | This package will lets you handle downloading the icon sets
    | from NPM. In rare situations, you may want to change the location where
    | icon sets are downloaded.
    |
    | By default, the package will find the icon sets in your `node_modules`
    | directory at the root of your project but you're free to change this.
    |
    | Iconify allows you to install individual icon sets or all of them.
    | This package will work with both scenarios. It will default to using the
    | icons the individual icon set installs, otherwise it will use the icons
    | from the full `@iconify/json` package.
    |
    | See this link for information on installing all the sets or specific sets:
    | https://github.com/unplugin/unplugin-icons?tab=readme-ov-file#icons-data
    |
    */

    'icons_location' => base_path('node_modules'),

    /*
    |--------------------------------------------------------------------------
    | Iconify Icon Providers
    |--------------------------------------------------------------------------
    | By default, this package will use your Laravel application as the provider
    | for all iconify icons you install with NPM. If you would like to use a
    | different provider for a specific icon set, you can specify it here.
    |
    | @see https://iconify.design/docs/api/config.html#using-iconifyproviders
    | @see https://iconify.design/docs/api/providers.html#api-config
    */

    'custom_providers' => [
        // Example:
        // 'mdi' => [
        //     'resources' => [
        //         'https://api.iconify.design',
        //         'https://example.com',
        //     ],
        //     'rotate' => 1000,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inline Icon Rendering
    |--------------------------------------------------------------------------
    | Configure the optional helper function and Blade component for rendering
    | Iconify icons directly to SVG without an HTTP request.
    |
    | Defaults support any SVG attribute (for example class, data-*, style) as
    | well as any render option (width, height, rotate, flip, inline, color).
    |
    */

    'inline' => [
        'enabled' => true,

        'defaults' => [
            'class' => '',
            // Any default SVG attribute or render option is supported.
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

    /*
    |--------------------------------------------------------------------------
    | Cache driver
    |--------------------------------------------------------------------------
    | Cache driver to use for storing the icon set caches. Indices are used to speed up
    | icon response. Defaults to your application's default cache driver.
    |
    */

    'cache_store' => env('ICONIFY_API_CACHE_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | Cache key prefix
    |--------------------------------------------------------------------------
    | Iconify API prefixes all the cache keys created with this value. If for
    | some reason you would like to change this prefix, you can do so here.
    |
    | The format of Iconify API cache keys is:
    | {cache-prefix}:{icon-set-prefix}:icon:{shape-version}:{icon-name} for icons, and
    | {cache-prefix}:{icon-set-prefix}:meta:{info|summary|file:{type}} for icon set
    | metadata.
    |
    */

    'cache_key_prefix' => 'iconify-icons',

    /*
    |--------------------------------------------------------------------------
    | Cached miss lifetime
    |--------------------------------------------------------------------------
    | How many seconds a "this icon does not exist" result stays cached. A found
    | icon never expires — it cannot change while the installed version of the icon
    | set stays the same — but a miss can be minted for any name a caller invents, so
    | it is kept only briefly. Set this to 0 to stop caching misses entirely.
    |
    | Nothing keys off the icon set file, so upgrading an icon package does not
    | invalidate anything. Run `php artisan cache:clear` after `npm update
    | @iconify/json` or after upgrading an `@iconify-json/*` package, or a redrawn
    | icon keeps serving its old body.
    |
    */

    'not_found_cache_ttl' => 300,

    /*
    |--------------------------------------------------------------------------
    | Maximum icons per API request
    |--------------------------------------------------------------------------
    | Upper bound on the number of names accepted in the `icons` query string of
    | the icon set routes. Requests above it are rejected with a 400. Set this to
    | 0 to remove the limit.
    |
    */

    'max_icons_per_request' => 200,
];
