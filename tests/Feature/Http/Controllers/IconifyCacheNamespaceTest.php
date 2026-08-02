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

it('rejects icon names that upstream matchIconName would not accept', function () {
    $response = test()->get(route('iconify-api.set-json.show', [
        'set' => 'bytesize',
        'icons' => 'activity,info:summary:2,../../etc/passwd,UPPER',
    ]));

    $response->assertStatus(200);

    expect($response->json('icons'))->toHaveKey('activity')
        ->and($response->json('not_found'))->toBe(['info:summary:2', '../../etc/passwd', 'UPPER']);

    // A rejected name must never reach the cache key builder at all.
    $keys = array_keys(Cache::store('array')->getStore()->all());

    expect($keys)->toContain('iconify-icons:bytesize:icon:2:activity');

    foreach ($keys as $key) {
        expect($key)->not->toContain('summary:2:')
            ->and($key)->not->toContain('passwd')
            ->and($key)->not->toContain('UPPER');
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
