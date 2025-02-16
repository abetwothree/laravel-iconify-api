<?php

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

it('tests loading the full collections', function () {
    $response = test()->get(route('iconify-api.collections.index'));
    $response->assertStatus(200);

    $response->assertJsonStructure([
        '*' => [
            'name',
            'total',
            'author',
            'license',
            'palette',
        ],
    ]);
});

it('tests loading full collection from individual sets', function () {
    LaravelIconifyApi::partialMock()
        ->shouldReceive('fullSetLocation')
        ->andReturn('wrong/path');

    $response = test()->get(route('iconify-api.collections.index'));
    $response->assertStatus(200);

    $response->assertJsonStructure([
        '*' => [
            'name',
            'total',
            'author',
            'license',
            'palette',
        ],
    ]);
});

it('tests getting an error if no prefix is specified', function (string $prefix) {
    $response = test()->get(route('iconify-api.collections.show', ['prefix' => $prefix]));
    $response->assertStatus(200);

    $response->assertJsonStructure([
        'name',
        'total',
        'author',
        'license',
        'palette',
    ]);
})->with([
    ['mdi'],
    ['heroicons'],
    ['material-symbols'],
    ['codicon'],
]);

it('tests not passing a prefix and getting an exception', function () {
    $response = test()->get(route('iconify-api.collections.show'));
    $response->assertStatus(404);
});
