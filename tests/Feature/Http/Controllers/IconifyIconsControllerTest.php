<?php

declare(strict_types=1);

beforeEach(function () {
    config()->set('iconify-api.cache_store', null);
});

it('tests loading an icon', function (string $route) {
    $response = test()->get(route($route, ['set' => 'mdi-light', 'icons' => 'home']));
    $response->assertStatus(200);

    $response->assertJsonStructure([
        'prefix',
        'lastModified',
        'aliases',
        'width',
        'height',
        'icons' => [
            'home' => [
                'body',
            ],
        ],
    ]);
})->with([
    ['iconify-api.set-icons-json.show'],
    ['iconify-api.set-json.show'],
]);

it('tests loading multiple icons', function (string $route) {
    $response = test()->get(route($route, ['set' => 'bytesize', 'icons' => 'activity,alert,bad-icon']));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'prefix',
        'lastModified',
        'aliases',
        'width',
        'height',
        'icons' => [
            'activity' => [
                'body',
            ],
            'alert' => [
                'body',
            ],
        ],
        'not_found',
    ]);

    expect($response->json('not_found'))->toBe(['bad-icon']);
})->with([
    ['iconify-api.set-icons-json.show'],
    ['iconify-api.set-json.show'],
]);

it('tests loading icons returns multiple not found icons', function (string $route) {
    $response = test()->get(route($route, ['set' => 'bytesize', 'icons' => 'activity,missing-one,missing-two']));
    $response->assertStatus(200);

    $response->assertJsonStructure([
        'prefix',
        'lastModified',
        'aliases',
        'width',
        'height',
        'icons' => [
            'activity' => [
                'body',
            ],
        ],
        'not_found',
    ]);

    expect($response->json('not_found'))->toBe(['missing-one', 'missing-two']);
})->with([
    ['iconify-api.set-icons-json.show'],
    ['iconify-api.set-json.show'],
]);

it('returns the icon set root offsets so clients build the right viewBox', function (string $route) {
    $response = test()->get(route($route, ['set' => 'jam', 'icons' => 'home']));
    $response->assertStatus(200);

    expect($response->json())->toMatchArray([
        'prefix' => 'jam',
        'left' => -2,
        'top' => -2,
        'width' => 24,
        'height' => 24,
    ]);
})->with([
    ['iconify-api.set-icons-json.show'],
    ['iconify-api.set-json.show'],
]);

it('tests getting an error if icons are not specified', function (string $route) {
    $response = test()->get(route($route, ['set' => 'mdi-light']));
    $response->assertStatus(404);

    $response->assertJsonStructure([
        'error',
    ]);
})->with([
    ['iconify-api.set-icons-json.show'],
    ['iconify-api.set-json.show'],
]);

it('rejects an icons parameter that is not a string', function (string $route) {
    $response = test()->get(route($route, ['set' => 'mdi', 'icons' => ['home', 'account']]));

    $response->assertStatus(400);

    expect($response->json('error'))->toBe('The icons parameter must be a comma separated string');
})->with([
    ['iconify-api.set-icons-json.show'],
    ['iconify-api.set-json.show'],
]);
