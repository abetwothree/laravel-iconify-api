<?php

use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinder;

it('can find info', function (
    string $set
) {
    $iconFinder = resolve(IconSetInfoSummaryFinder::class);
    $info = $iconFinder->find($set);

    expect($info)->toBeArray()
        ->toHaveKeys(['prefix', 'lastModified'])
        ->prefix->toBe($set)
        ->lastModified->toBeInt();

    if (isset($info['width'])) {
        expect($info['width'])->toBeInt();
    }

    if (isset($info['height'])) {
        expect($info['height'])->toBeInt();
    }
})->with([
    ['mdi'],
    ['heroicons'],
    ['academicons'],
    ['bi'],
]);
