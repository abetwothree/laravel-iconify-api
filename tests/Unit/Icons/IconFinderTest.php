<?php

use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;

it('can find multiple icons', function (
    string $set,
    array $icons
) {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find($set, $icons);

    expect($icons)->each(function ($icon) {
        $icon->toBeArray()
            ->toHaveKeys(['aliases', 'icons'])
            ->aliases->toBeArray()
            ->icons->toBeArray();
    });
})->with([
    ['mdi', ['home', 'account']],
    ['mdi', ['home', 'account', 'abacus']],
    ['mdi', ['account-cash']],
    ['heroicons', ['academic-cap', 'adjustments-vertical']],
    ['heroicons', ['academic-cap', 'adjustments-vertical', 'chart-pie-16-solid']],
    ['heroicons', ['swatch', 'code-solid']],
]);

it('can find a specific icon', function (
    string $set,
    array $icon,
) {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find($set, $icon);

    expect($icons)->toBeArray()
        ->toHaveKeys($icon)
        ->each(function ($icon) {
            $icon->toBeArray()
                ->toHaveKeys(['aliases', 'icons'])
                ->aliases->toBeArray()
                ->icons->toBeArray();
        });
})->with([
    ['mdi', ['home']],
    ['heroicons', ['academic-cap']],
]);

it('returns proper response but fails to find icon', function (
    string $set,
    array $icon,
) {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find($set, $icon);

    expect($icons)
        ->toBeArray()
        ->toHaveKeys($icon)
        ->each(function ($icon) {
            $icon->toBeArray()
                ->toHaveKeys(['aliases', 'icons', 'not_found'])
                ->aliases->toBeArray()
                ->icons->toBeArray()
                ->not_found->toBeArray()
                ->not_found->toHaveCount(1)
                ->not_found->each->toBe('not-an-icon');
        });
})->with([
    ['mdi', ['not-an-icon']],
    ['heroicons', ['not-an-icon']],
]);
