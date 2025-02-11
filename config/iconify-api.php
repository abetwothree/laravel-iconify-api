<?php

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
    | {cache-prefix}:{icon-set-prefix}:{icon-name}
    |
    */

    'cache_key_prefix' => 'iconify-icons',
];
