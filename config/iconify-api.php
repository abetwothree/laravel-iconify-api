<?php

return [
    'enabled' => env('ICONIFY_API_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Iconify API Domain
    |--------------------------------------------------------------------------
    | You may change the domain where Iconify API should be active.
    | If the domain is empty, all domains will be valid.
    |
    */

    'route_domain' => '',

    /*
    |--------------------------------------------------------------------------
    | Iconify API Route
    |--------------------------------------------------------------------------
    | Iconify API will be available under this URL.
    |
    */

    'route_path' => 'iconify-api',

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
    | Cache driver
    |--------------------------------------------------------------------------
    | Cache driver to use for storing the icon set caches. Indices are used to speed up
    | icon response navigation. Defaults to your application's default cache driver.
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
    | {cache-prefix}:{icon-set}:{icon-name}
    |
    */

    'cache_key_prefix' => 'iconify-icons',
];
