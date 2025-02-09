<?php

beforeEach(function () {
    config()->set('iconify-api.cache_store', null);
});

it('tests loading an icon', function () {
    $response = test()->get(route('iconify-api.set.show', ['set' => 'mdi-light', 'icons' => 'home']));
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
});

it('tests loading multiple icons', function () {
    $response = test()->get(route('iconify-api.set.show', ['set' => 'bytesize', 'icons' => 'activity,alert']));
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
});

it('tests getting an error if icons are not specified', function () {
    $response = test()->get(route('iconify-api.set.show', ['set' => 'mdi-light']));
    $response->assertStatus(404);

    $response->assertJsonStructure([
        'error',
    ]);
});
