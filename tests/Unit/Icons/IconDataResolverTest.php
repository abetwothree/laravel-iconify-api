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

it('carries a fractional rotation through the merge instead of collapsing it', function () {
    $resolver = new IconDataResolver;

    $summed = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'rotate' => 0.5]],
        'aliases' => ['half' => ['parent' => 'base', 'rotate' => 0.5]],
    ], 'half');

    // Two fractions that add up to a whole rotation must still rotate.
    expect($summed['rotate'])->toBe(1.0);

    $unsummed = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'rotate' => 1.5]],
        'aliases' => [],
    ], 'base');

    expect($unsummed['rotate'])->toBe(1.5);
});

it('adds a fractional icon set root rotation to the icon rotation', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve(
        ['icons' => ['home' => ['body' => '<path/>', 'rotate' => 0.5]], 'aliases' => []],
        'home',
        ['rotate' => 0.5],
    );

    expect($result['rotate'])->toBe(1.0);
});

it('keeps a whole number float rotation', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['home' => ['body' => '<path/>', 'rotate' => 2.0]],
        'aliases' => [],
    ], 'home');

    expect($result['rotate'])->toBe(2.0);
});

it('xors flips across an alias chain', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'hFlip' => true]],
        'aliases' => ['flipped' => ['parent' => 'base', 'hFlip' => true]],
    ], 'flipped');

    expect($result['hFlip'] ?? false)->toBeFalse();
});

it('treats a float zero flip as falsy, the way JavaScript does', function (string $property) {
    $resolver = new IconDataResolver;

    // JSON decodes a literal `0.0` to a PHP float, and `0.0 !== 0` is true, so a
    // type-strict falsy list would read it as truthy and mirror the icon.
    $onIcon = $resolver->resolve([
        'icons' => ['home' => ['body' => '<path/>', $property => 0.0]],
        'aliases' => [],
    ], 'home');

    expect($onIcon)->not->toHaveKey($property);

    $onAlias = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>']],
        'aliases' => ['zeroed' => ['parent' => 'base', $property => 0.0]],
    ], 'zeroed');

    expect($onAlias)->not->toHaveKey($property);

    // Upstream leaves the key present but false here, because the root declared it:
    // `if (key in parent && !(key in result)) result[key] = defaultIconTransformations[key]`.
    $onRoot = $resolver->resolve(
        ['icons' => ['home' => ['body' => '<path/>']], 'aliases' => []],
        'home',
        [$property => 0.0],
    );

    expect($onRoot[$property])->toBeFalse();

    // A float zero must not cancel a real flip either.
    $withRealFlip = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', $property => true]],
        'aliases' => ['zeroed' => ['parent' => 'base', $property => 0.0]],
    ], 'zeroed');

    expect($withRealFlip[$property])->toBeTrue();
})->with([['hFlip'], ['vFlip']]);

it('treats a NAN flip as falsy, the way JavaScript does', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['home' => ['body' => '<path/>', 'hFlip' => NAN]],
        'aliases' => [],
    ], 'home');

    expect($result)->not->toHaveKey('hFlip');
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

it('lets an alias override the parent body', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path d="parent"/>', 'width' => 24]],
        'aliases' => ['custom' => ['parent' => 'base', 'body' => '<path d="child"/>']],
    ], 'custom');

    expect($result['body'])->toBe('<path d="child"/>');
    expect($result['width'])->toBe(24);
});

it('propagates the hidden flag from an alias', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>']],
        'aliases' => ['deprecated' => ['parent' => 'base', 'hidden' => true]],
    ], 'deprecated');

    expect($result['hidden'])->toBeTrue();
});

it('treats an explicitly null property as present', function () {
    $resolver = new IconDataResolver;

    $result = $resolver->resolve([
        'icons' => ['base' => ['body' => '<path/>', 'width' => 24]],
        'aliases' => ['nulled' => ['parent' => 'base', 'width' => null]],
    ], 'nulled');

    expect($result)->toHaveKey('width');
    expect($result['width'])->toBeNull();
});
