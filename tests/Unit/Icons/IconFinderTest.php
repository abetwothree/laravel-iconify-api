<?php

use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;

it('can find multiple icons', function () {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find('mdi', ['home', 'account']);

    expect($icons)->each(function ($icon) {
        $icon->toBeArray()
            ->toHaveKeys(['prefix', 'lastModified', 'width', 'height', 'aliases', 'icons'])
            ->prefix->toBe('mdi')
            ->lastModified->toBeInt()
            ->width->toBeInt()
            ->height->toBeInt()
            ->aliases->toBeArray()
            ->icons->toBeArray();
    });
});

it('can find a specific icon', function () {
    $iconFinder = resolve(IconFinder::class);

    $icons = $iconFinder->find('mdi', ['home']);

    expect($icons)->toBeArray()
        ->toHaveKeys(['home'])
        ->each(function ($icon) {
            $icon->toBeArray()
                ->toHaveKeys(['prefix', 'lastModified', 'width', 'height', 'aliases', 'icons'])
                ->prefix->toBe('mdi')
                ->lastModified->toBeInt()
                ->width->toBeInt()
                ->height->toBeInt()
                ->aliases->toBeArray()
                ->icons->toBeArray();
        });
});

it('returns proper response but fails to find icon', function () {
    $iconFinder = resolve(IconFinder::class);

    $icons = $iconFinder->find('mdi', ['not-an-icon']);

    expect($icons)
        ->toBeArray()
        ->toHaveKeys(['not-an-icon'])
        ->each(function ($icon) {
            $icon->toBeArray()
                ->toHaveKeys(['prefix', 'lastModified', 'width', 'height', 'aliases', 'icons', 'not_found'])
                ->prefix->toBe('mdi')
                ->lastModified->toBeInt()
                ->width->toBeInt()
                ->height->toBeInt()
                ->aliases->toBeArray()
                ->icons->toBeArray()
                ->not_found->toBeArray()
                ->not_found->toHaveCount(1)
                ->not_found->each->toBe('not-an-icon');
        });
});
