<?php

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use Illuminate\Support\Facades\Cache;

it('covers cache repository traits getters and setters', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');

    $repo = new CacheRepository;

    $iconSetInfo = [
        'prefix' => 'mdi',
        'name' => 'Material Design Icons',
    ];

    $repo->setIconSetInfo('mdi', $iconSetInfo);
    expect($repo->getIconSetInfo('mdi'))->toBe($iconSetInfo);

    Cache::store('array')->put('iconify-icons:mdi:meta:info', 'not-array');
    expect($repo->getIconSetInfo('mdi'))->toBeNull();

    $summary = [
        'prefix' => 'mdi',
        'lastModified' => 1,
    ];

    $repo->setIconSetInfoSummary('mdi', $summary);
    expect($repo->getIconSetInfoSummary('mdi'))->toBe($summary);

    Cache::store('array')->put('iconify-icons:mdi:meta:summary', 'not-array');
    expect($repo->getIconSetInfoSummary('mdi'))->toBeNull();

    $repo->setFileSet('mdi', '/tmp/mdi.json', 'icons');
    expect($repo->getFileSet('mdi', 'icons'))->toBe('/tmp/mdi.json');

    Cache::store('array')->put('iconify-icons:mdi:meta:file:icons', ['not-string']);
    expect($repo->getFileSet('mdi', 'icons'))->toBeNull();

    $iconData = [
        'icons' => ['home' => ['body' => '<path />']],
        'aliases' => [],
        'defaults' => [],
    ];

    $repo->setIcon('mdi', 'home', $iconData);

    $icons = $repo->getIcons('mdi', ['home', 'missing']);
    expect($icons['found'])->toHaveKey('home');
    expect($icons['not_found'])->toBe(['missing']);
});

it('serves a cached icon written without the optional defaults key', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');

    $repo = new CacheRepository;

    // `defaults` is optional by contract, so a finder that omits it must still cache.
    $entry = [
        'icons' => ['no-defaults' => ['body' => '<path />']],
        'aliases' => [],
    ];

    $repo->setIcon('test', 'no-defaults', $entry);

    $result = $repo->getIcons('test', ['no-defaults']);

    expect($result['found'])->toBe(['no-defaults' => $entry]);
    expect($result['not_found'])->toBe([]);
});

it('treats an entry cached under an older shape version as a miss', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');

    $repo = new CacheRepository;

    // Written by a release before the shape version was introduced.
    Cache::store('array')->put('iconify-icons:test:stale', [
        'icons' => ['stale' => ['body' => '<path />']],
        'aliases' => [],
    ]);

    $result = $repo->getIcons('test', ['stale']);

    expect($result['found'])->toBe([]);
    expect($result['not_found'])->toBe(['stale']);
});
