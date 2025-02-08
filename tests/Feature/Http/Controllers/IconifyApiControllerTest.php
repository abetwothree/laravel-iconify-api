<?php

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
    $response = test()->get(route('iconify-api.set.show', ['set' => 'mdi-light', 'icons' => 'home,account-arrow-up-outline']));
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
            'account-arrow-up-outline' => [
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
