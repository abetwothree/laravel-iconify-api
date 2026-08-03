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

it('copies the icon set root left and top offsets', function () {
    $iconFinder = resolve(IconSetInfoSummaryFinder::class);

    // `jam` declares a negative origin: left/top must survive or every icon in the
    // set renders with the wrong viewBox.
    expect($iconFinder->find('jam'))
        ->toMatchArray([
            'prefix' => 'jam',
            'left' => -2,
            'top' => -2,
            'width' => 24,
            'height' => 24,
        ]);
});

it('omits root properties the icon set does not declare', function () {
    $iconFinder = resolve(IconSetInfoSummaryFinder::class);

    expect($iconFinder->find('mdi'))
        ->not->toHaveKey('left')
        ->not->toHaveKey('top')
        ->not->toHaveKey('provider');
});
