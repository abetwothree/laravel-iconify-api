<?php

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config()->set('cache.default', 'array');
    config()->set('iconify-api.cache_store', 'array');
    Cache::store('array')->clear();
});

it('does not let an icon named info overwrite the icon set metadata', function () {
    // 70 of the 235 sets bundled in @iconify/json ship an icon literally named
    // `info`, so this is an ordinary request, not a crafted one.
    $icons = test()->get(route('iconify-api.set-json.show', ['set' => 'codicon', 'icons' => 'info']));
    $icons->assertStatus(200);
    expect($icons->json('icons.info.body'))->toBeString();

    $collection = test()->get(route('iconify-api.collections.show', ['prefix' => 'codicon']));
    $collection->assertStatus(200);

    expect($collection->json())
        ->toHaveKeys(['name', 'total', 'author', 'license'])
        ->and($collection->json('name'))->toBe('Codicons')
        ->and($collection->json())->not->toHaveKey('defaults')
        ->and($collection->json())->not->toHaveKey('body');

    $collections = test()->get(route('iconify-api.collections.index'));
    $collections->assertStatus(200);

    expect($collections->json('codicon.name'))->toBe('Codicons');
});

it('does not let a crafted icon name overwrite the icon set summary', function () {
    // `info:summary:2` addressed the summary key exactly under the flat namespace.
    $poison = test()->get(route('iconify-api.set-json.show', ['set' => 'jam', 'icons' => 'info:summary:2']));
    $poison->assertStatus(200);

    $icons = test()->get(route('iconify-api.set-json.show', ['set' => 'jam', 'icons' => 'home']));
    $icons->assertStatus(200);

    expect($icons->json())
        ->toMatchArray([
            'prefix' => 'jam',
            'left' => -2,
            'top' => -2,
            'width' => 24,
            'height' => 24,
        ])
        ->and($icons->json())->not->toHaveKey('defaults');
});

it('looks up icon names that upstream matchIconName would not accept', function () {
    // A set reached through a custom `icons_location` may be hand-authored, so names
    // are not filtered up front: every one goes through the finder like any other.
    $response = test()->get(route('iconify-api.set-json.show', [
        'set' => 'bytesize',
        'icons' => 'activity,info:summary:2,../../etc/passwd,UPPER',
    ]));

    $response->assertStatus(200);

    expect($response->json('icons'))->toHaveKey('activity')
        ->and($response->json('not_found'))->toBe(['info:summary:2', '../../etc/passwd', 'UPPER']);

    $keys = array_keys(Cache::store('array')->getStore()->all());

    // A name a cache key can hold is written verbatim; one carrying the key separator
    // or a path separator is replaced by a hash of the whole name.
    expect($keys)->toContain('iconify-icons:bytesize:icon:2:activity')
        ->and($keys)->toContain('iconify-icons:bytesize:icon:2:UPPER')
        ->and($keys)->toContain('iconify-icons:bytesize:icon:2:h:'.hash('sha256', 'info:summary:2'))
        ->and($keys)->toContain('iconify-icons:bytesize:icon:2:h:'.hash('sha256', '../../etc/passwd'))
        ->and($keys)->not->toContain('iconify-icons:bytesize:icon:2:info:summary:2')
        ->and($keys)->not->toContain('iconify-icons:bytesize:icon:2:../../etc/passwd');

    // And the icon set metadata this request also cached is untouched by any of them.
    expect(Cache::store('array')->get('iconify-icons:bytesize:meta:summary'))
        ->toHaveKey('prefix')
        ->and(Cache::store('array')->get('iconify-icons:bytesize:meta:file:icons'))
        ->toBeString();
});

it('keeps the icon set metadata intact for names that spell a metadata key', function () {
    // With no name filter in front of it, the key builder is the only thing standing
    // between these names and the metadata keys they are written to imitate. `info` is
    // the name the original bug used; the rest spell every metadata key this package
    // writes, both `fileSetKey()` types included.
    $response = test()->get(route('iconify-api.set-json.show', [
        'set' => 'codicon',
        'icons' => 'info,meta:info,meta:summary,meta:file:icons,meta:file:info',
    ]));

    $response->assertStatus(200);

    expect($response->json('icons.info.body'))->toBeString()
        ->and($response->json('not_found'))
        ->toBe(['meta:info', 'meta:summary', 'meta:file:icons', 'meta:file:info']);

    $collection = test()->get(route('iconify-api.collections.show', ['prefix' => 'codicon']));
    $collection->assertStatus(200);

    expect($collection->json())
        ->toHaveKeys(['name', 'total', 'author', 'license'])
        ->and($collection->json('name'))->toBe('Codicons')
        ->and($collection->json())->not->toHaveKey('body');

    $collections = test()->get(route('iconify-api.collections.index'));
    $collections->assertStatus(200);

    expect($collections->json('codicon.name'))->toBe('Codicons');

    expect(Cache::store('array')->get('iconify-icons:codicon:meta:info'))->toHaveKey('name')
        ->and(Cache::store('array')->get('iconify-icons:codicon:meta:summary'))->toHaveKey('prefix')
        ->and(Cache::store('array')->get('iconify-icons:codicon:meta:file:icons'))->toBeString()
        ->and(Cache::store('array')->get('iconify-icons:codicon:meta:file:info'))->toBeString();
});

it('rejects a request that asks for more icons than the configured limit', function () {
    config()->set('iconify-api.max_icons_per_request', 5);

    $names = array_map(static fn (int $i): string => "junk-{$i}", range(0, 9));

    $response = test()->get(route('iconify-api.set-json.show', [
        'set' => 'bytesize',
        'icons' => implode(',', $names),
    ]));

    $response->assertStatus(400);
    expect($response->json('error'))->toContain('5');

    // Nothing about a rejected request may reach the cache.
    expect(array_keys(Cache::store('array')->getStore()->all()))->toBe([]);
});

it('does not leave permanent cache entries behind for names that do not exist', function () {
    config()->set('iconify-api.not_found_cache_ttl', 300);

    $names = array_map(static fn (int $i): string => "junk-{$i}", range(0, 9));

    test()->get(route('iconify-api.set-json.show', [
        'set' => 'bytesize',
        'icons' => implode(',', $names),
    ]))->assertStatus(200);

    $before = array_keys(Cache::store('array')->getStore()->all());
    expect($before)->toContain('iconify-icons:bytesize:icon:2:junk-0');

    test()->travel(301)->seconds();

    foreach ($names as $name) {
        expect(Cache::store('array')->get("iconify-icons:bytesize:icon:2:{$name}"))->toBeNull();
    }
});

it('never writes a cache key that another key builder could address', function () {
    test()->get(route('iconify-api.set-json.show', ['set' => 'codicon', 'icons' => 'info']));
    test()->get(route('iconify-api.collections.show', ['prefix' => 'codicon']));

    $keys = array_keys(Cache::store('array')->getStore()->all());

    expect($keys)->toContain('iconify-icons:codicon:icon:2:info')
        ->and($keys)->toContain('iconify-icons:codicon:meta:info')
        ->and($keys)->toContain('iconify-icons:codicon:meta:summary')
        ->and($keys)->toContain('iconify-icons:codicon:meta:file:icons');
});
