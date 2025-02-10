<?php

use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinder;

it('can find info', function (
    string $set
) {
    $iconFinder = resolve(IconSetInfoFinder::class);
    $info = $iconFinder->find($set);

    expect($info)->toBeArray()
        ->toHaveKeys(['prefix', 'lastModified', 'width', 'height'])
        ->prefix->toBe($set)
        ->lastModified->toBeInt()
        ->width->toBeInt()
        ->height->toBeInt();
})->with([
    ['mdi'],
    ['heroicons'],
    ['academicons'],
    ['bi'],
]);
