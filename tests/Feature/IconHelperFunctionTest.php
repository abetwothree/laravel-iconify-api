<?php

declare(strict_types=1);

it('renders an svg with the helper function', function () {
    $svg = icon('heroicons:academic-cap');

    expect($svg)
        ->toBeString()
        ->toContain('<svg ')
        ->toContain('viewBox=')
        ->toContain('</svg>');
});

it('applies helper options to rendered icon', function () {
    $svg = icon('heroicons:clock', [
        'class' => 'w-6 h-6',
        'data-slot' => 'icon',
    ]);

    expect($svg)
        ->toContain('class="iconify iconify--heroicons w-6 h-6"')
        ->toContain('data-slot="icon"');
});

it('applies arbitrary configured defaults and allows per-call overrides', function () {
    config()->set('iconify-api.inline.defaults', [
        'class' => 'size-5',
        'data-source' => 'default',
        'style' => 'color: red;',
    ]);

    $svg = icon('heroicons:clock', [
        'class' => 'w-6',
        'data-source' => 'override',
        'data-slot' => 'icon',
    ]);

    expect($svg)
        ->toContain('class="iconify iconify--heroicons size-5 w-6"')
        ->toContain('data-source="override"')
        ->toContain('data-slot="icon"')
        ->toContain('style="color: red;"');
});

it('applies configured defaults for customisation options', function () {
    config()->set('iconify-api.inline.defaults', [
        'width' => '2em',
        'inline' => true,
        'rotate' => 1,
        'h-flip' => true,
        'color' => 'red',
    ]);

    $svg = icon('heroicons:clock');

    expect($svg)
        ->toContain('width="2em"')
        ->toContain('rotate(90')
        ->toContain('scale(-1 1)')
        ->toContain('color: red;')
        ->toContain('vertical-align: -0.125em;')
        ->not->toContain('rotate="')
        ->not->toContain('h-flip=')
        ->not->toContain('inline=');
});

it('prefers per-call customisations over configured defaults', function () {
    config()->set('iconify-api.inline.defaults', [
        'width' => '2em',
        'inline' => true,
        'rotate' => 1,
    ]);

    $svg = icon('heroicons:clock', [
        'width' => '24',
        'inline' => false,
        'rotate' => 0,
    ]);

    expect($svg)
        ->toContain('width="24"')
        ->not->toContain('rotate(')
        ->not->toContain('vertical-align');
});

it('prefers per-call width and height over configured defaults', function () {
    config()->set('iconify-api.inline.defaults', [
        'width' => '2em',
        'height' => '2em',
    ]);

    $svg = icon('heroicons:clock', [
        'width' => '24',
        'height' => '32',
    ]);

    expect($svg)
        ->toContain('width="24"')
        ->toContain('height="32"');
});

it('returns empty string for unknown icon in helper', function () {
    expect(icon('heroicons:not-an-icon'))->toBe('');
});

it('is declared by composer autoloading rather than at boot', function () {
    expect(realpath((string) (new ReflectionFunction('icon'))->getFileName()))
        ->toBe(realpath(__DIR__.'/../../src/helpers.php'));
});
