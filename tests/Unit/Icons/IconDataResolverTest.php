<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconDataResolver;

it('returns a plain icon untouched', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['home' => ['body' => '<path d="M0 0"/>', 'width' => 24, 'height' => 24]],
        'aliases' => [],
    ], 'home');

    expect($result)->toEqual(['body' => '<path d="M0 0"/>', 'width' => 24, 'height' => 24]);
});

it('returns null for a missing icon', function () {
    $resolver = new IconDataResolver;

    expect($resolver->resolve(['icons' => [], 'aliases' => []], 'nope'))->toBeNull();
});

it('returns null for a cyclic alias chain', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => [],
        'aliases' => ['a' => ['parent' => 'b'], 'b' => ['parent' => 'a']],
    ], 'a');

    expect($result)->toBeNull();
});

it('accumulates rotation additively across a multi level alias chain', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'width' => 24, 'height' => 24, 'rotate' => 1]],
        'aliases' => [
            'second' => ['parent' => 'base', 'rotate' => 1],
            'first' => ['parent' => 'second', 'rotate' => 1],
        ],
    ], 'first');

    expect($result['rotate'])->toBe(3);
});

it('xors flips across an alias chain', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'hFlip' => true]],
        'aliases' => ['flipped' => ['parent' => 'base', 'hFlip' => true]],
    ], 'flipped');

    expect($result['hFlip'] ?? false)->toBeFalse();
});

it('lets the child alias win for dimensions', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'width' => 24, 'height' => 24]],
        'aliases' => ['wide' => ['parent' => 'base', 'width' => 48]],
    ], 'wide');

    expect($result['width'])->toBe(48);
    expect($result['height'])->toBe(24);
});

it('merges icon set root defaults as the outermost parent', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve(
        ['icons' => ['home' => ['body' => '<path/>']], 'aliases' => []],
        'home',
        ['width' => 24, 'height' => 24],
    );

    expect($result['width'])->toBe(24);
    expect($result['height'])->toBe(24);
});

it('lets the icon override icon set root defaults', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve(
        ['icons' => ['home' => ['body' => '<path/>', 'width' => 32]], 'aliases' => []],
        'home',
        ['width' => 24, 'height' => 24],
    );

    expect($result['width'])->toBe(32);
    expect($result['height'])->toBe(24);
});

it('adds icon set root rotation to the icon rotation', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve(
        ['icons' => ['home' => ['body' => '<path/>', 'rotate' => 1]], 'aliases' => []],
        'home',
        ['rotate' => 2],
    );

    expect($result['rotate'])->toBe(3);
});

it('resets a transformation to its default when the parent set it and the xor cancels', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve(
        ['icons' => ['home' => ['body' => '<path/>', 'vFlip' => true]], 'aliases' => []],
        'home',
        ['vFlip' => true],
    );

    expect($result['vFlip'])->toBeFalse();
});
