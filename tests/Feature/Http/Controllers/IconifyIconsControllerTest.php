<?php

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
    $response = test()->get(route($route, ['set' => 'bytesize', 'icons' => 'activity,alert']));
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
