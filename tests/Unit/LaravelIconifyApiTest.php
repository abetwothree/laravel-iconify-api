<?php

use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;

it('covers location helper methods and type branches', function () {
    $base = sys_get_temp_dir().'/iconify-api-locations-'.uniqid('', true);
    mkdir($base, 0777, true);

    config()->set('iconify-api.icons_location', $base);

    $api = new LaravelIconifyApi;

    expect($api->iconsLocation())->toBe($base);
    expect($api->fullSetLocation())->toBe($base.'/@iconify');
    expect($api->fullSetsJsonLocation())->toBe($base.'/@iconify/json/json');
    expect($api->singleSetLocation())->toBe($base.'/@iconify-json');
    expect($api->singleSetJsonLocation('mdi', 'icons'))->toBe($base.'/@iconify-json/mdi/icons.json');
    expect($api->singleSetJsonLocation('mdi', 'info'))->toBe($base.'/@iconify-json/mdi/info.json');
    expect($api->singleSetJsonLocation('mdi', 'metadata'))->toBe($base.'/@iconify-json/mdi/metadata.json');
    expect($api->singleSetJsonLocation('mdi', 'chars'))->toBe($base.'/@iconify-json/mdi/chars.json');

    expect(fn () => $api->singleSetJsonLocation('mdi', 'invalid'))->toThrow(Exception::class);
});

it('covers cacheStore and domain validations', function () {
    $api = new LaravelIconifyApi;

    config()->set('iconify-api.cache_store', 'array');
    expect($api->cacheStore())->toBe('array');

    config()->set('iconify-api.cache_store', null);
    config()->set('cache.default', 'file');
    expect($api->cacheStore())->toBe('file');

    config()->set('cache.default', ['bad']);
    expect(fn () => $api->cacheStore())->toThrow(Exception::class, 'Cache store must be a string');

    config()->set('iconify-api.route_domain', null);
    expect($api->domain())->toBeNull();

    config()->set('iconify-api.route_domain', '');
    expect($api->domain())->toBeNull();

    config()->set('iconify-api.route_domain', 'example.test');
    expect($api->domain())->toBe('example.test');

    config()->set('iconify-api.route_domain', ['bad']);
    expect(fn () => $api->domain())->toThrow(Exception::class, 'Domain must be a string or null');
});

it('covers prefixes discovery and picks up a newly installed set', function () {
    $base = sys_get_temp_dir().'/iconify-api-prefixes-'.uniqid('', true);

    mkdir($base.'/@iconify-json/mdi', 0777, true);
    mkdir($base.'/@iconify-json/heroicons', 0777, true);
    mkdir($base.'/@iconify/json/json', 0777, true);

    file_put_contents($base.'/@iconify/json/json/mdi.json', '{}');
    file_put_contents($base.'/@iconify/json/json/lucide.json', '{}');

    config()->set('iconify-api.icons_location', $base);

    $api = new LaravelIconifyApi;

    expect($api->prefixes())->toBe(['heroicons', 'lucide', 'mdi']);

    // A static memo would outlive the container reset on Octane and freeze the list at
    // whatever the first worker request saw, so a set installed later must show up —
    // both on the same instance and on a fresh one.
    mkdir($base.'/@iconify-json/tabler', 0777, true);

    expect($api->prefixes())->toBe(['heroicons', 'lucide', 'mdi', 'tabler']);
    expect((new LaravelIconifyApi)->prefixes())->toBe(['heroicons', 'lucide', 'mdi', 'tabler']);
});

it('covers path generation', function () {
    config()->set('iconify-api.route_path', 'iconify');
    expect((new LaravelIconifyApi)->path())->toBe('iconify/api');

    config()->set('iconify-api.route_path', 'iconify/');
    expect((new LaravelIconifyApi)->path())->toBe('iconify/api');
});
