<?php

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinderCached;
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

it('expires a cached miss but keeps a cached hit forever', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');
    config()->set('iconify-api.not_found_cache_ttl', 300);

    $repo = new CacheRepository;

    $repo->setIcon('test', 'junk', [
        'icons' => [],
        'aliases' => [],
        'defaults' => [],
        'not_found' => ['junk'],
    ]);

    $repo->setIcon('test', 'real', [
        'icons' => ['real' => ['body' => '<path />']],
        'aliases' => [],
        'defaults' => [],
    ]);

    expect($repo->getIcons('test', ['junk', 'real'])['found'])->toHaveKeys(['junk', 'real']);

    test()->travel(301)->seconds();

    $after = $repo->getIcons('test', ['junk', 'real']);

    expect($after['found'])->toHaveKey('real')
        ->and($after['not_found'])->toBe(['junk']);
});

it('does not cache a miss at all when the ttl is zero', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');
    config()->set('iconify-api.not_found_cache_ttl', 0);

    $repo = new CacheRepository;

    $repo->setIcon('test', 'junk', [
        'icons' => [],
        'aliases' => [],
        'defaults' => [],
        'not_found' => ['junk'],
    ]);

    expect($repo->getIcons('test', ['junk'])['not_found'])->toBe(['junk']);
    expect(cachedKeys())->toBe([]);
});

it('caches an icon whose name a cache key cannot hold', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');

    $repo = new CacheRepository;

    // A hand-authored set may use any of these. Memcached rejects a key holding a
    // space or a control character outright, and caps the key at 250 bytes.
    $names = [
        'a space',
        "a\nnewline",
        'a:colon',
        'a/slash',
        'ünïcøde',
        str_repeat('x', 129),
    ];

    $entries = [];

    foreach ($names as $name) {
        $entries[$name] = [
            'icons' => [$name => ['body' => "<path d=\"{$name}\" />"]],
            'aliases' => [],
            'defaults' => [],
        ];

        $repo->setIcon('hand-authored', $name, $entries[$name]);
    }

    $expected = array_map(
        static fn (string $name): string => 'iconify-icons:hand-authored:icon:2:h:'.hash('sha256', $name),
        $names,
    );

    // One key per name, all hashed, none of them equal to another.
    expect(cachedKeys())->toBe($expected)
        ->and(array_unique($expected))->toHaveCount(count($names));

    // And every one of them reads back as itself.
    $result = $repo->getIcons('hand-authored', $names);

    expect($result['found'])->toBe($entries)
        ->and($result['not_found'])->toBe([]);
});

it('keeps writing the name itself for every name a cache key can hold', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');

    $repo = new CacheRepository;

    // The longest name in the 235 sets bundled with `@iconify/json` is 99 bytes, so
    // the documented key format still describes every entry a real icon set writes.
    $names = ['home', 'account-circle', '24-hours', str_repeat('x', 128)];

    foreach ($names as $name) {
        $repo->setIcon('mdi', $name, [
            'icons' => [$name => ['body' => '<path />']],
            'aliases' => [],
            'defaults' => [],
        ]);
    }

    expect(cachedKeys())->toBe([
        'iconify-icons:mdi:icon:2:home',
        'iconify-icons:mdi:icon:2:account-circle',
        'iconify-icons:mdi:icon:2:24-hours',
        'iconify-icons:mdi:icon:2:'.str_repeat('x', 128),
    ]);
});

it('serves an icon with a hashed key from the cache instead of the finder', function () {
    config()->set('iconify-api.cache_store', 'array');
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_key_prefix', 'iconify-icons');

    $name = 'hand authored';

    $entry = [
        'icons' => [$name => ['body' => '<path />']],
        'aliases' => [],
        'defaults' => [],
    ];

    $finder = Mockery::mock(IconFinder::class);
    $finder->shouldReceive('find')->once()->with('hand-authored', [$name])->andReturn([$name => $entry]);

    $cached = new IconFinderCached($finder, new CacheRepository);

    expect($cached->find('hand-authored', [$name]))->toBe([$name => $entry]);

    // The second call must be a cache hit: `find()` is mocked `once()`, so reaching
    // the inner finder again fails the test.
    expect($cached->find('hand-authored', [$name]))->toBe([$name => $entry]);
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
