<?php

use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApiServiceProvider;

it('renders an svg with the helper function', function () {
    $helper = 'icon';
    $svg = $helper('heroicons:academic-cap');

    expect($svg)
        ->toBeString()
        ->toContain('<svg ')
        ->toContain('viewBox=')
        ->toContain('</svg>');
});

it('applies helper options to rendered icon', function () {
    $helper = 'icon';
    $svg = $helper('heroicons:clock', [
        'class' => 'w-6 h-6',
        'data-slot' => 'icon',
    ]);

    expect($svg)
        ->toContain('class="w-6 h-6"')
        ->toContain('data-slot="icon"');
});

it('applies arbitrary configured defaults and allows per-call overrides', function () {
    config()->set('iconify-api.inline.defaults', [
        'class' => 'size-5',
        'data-source' => 'default',
        'style' => 'color: red;',
    ]);

    $helper = 'icon';
    $svg = $helper('heroicons:clock', [
        'class' => 'w-6',
        'data-source' => 'override',
        'data-slot' => 'icon',
    ]);

    expect($svg)
        ->toContain('class="size-5 w-6"')
        ->toContain('data-source="override"')
        ->toContain('data-slot="icon"')
        ->toContain('style="color: red;"');
});

it('prefers per-call width and height over configured defaults', function () {
    config()->set('iconify-api.inline.defaults', [
        'width' => '2em',
        'height' => '2em',
    ]);

    $helper = 'icon';
    $svg = $helper('heroicons:clock', [
        'width' => '24',
        'height' => '32',
    ]);

    expect($svg)
        ->toContain('width="24"')
        ->toContain('height="32"');
});

it('returns empty string for unknown icon in helper', function () {
    $helper = 'icon';

    expect($helper('heroicons:not-an-icon'))->toBe('');
});

it('registers the helper with a custom function name', function () {
    config()->set('iconify-api.inline.helper.name', 'iconify_svg');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    $helper = 'iconify_svg';

    expect(function_exists($helper))->toBeTrue();
    expect($helper('heroicons:clock'))->toContain('<svg ');
});

if (! function_exists('existing_test_icon_helper')) {
    function existing_test_icon_helper(string $name, array $options = []): string
    {
        return 'existing-helper';
    }
}

if (! function_exists('inline_disabled_helper')) {
    function inline_disabled_helper(string $name, array $options = []): string
    {
        return 'inline-disabled-helper';
    }
}

it('does not override an existing helper function', function () {
    config()->set('iconify-api.inline.helper.name', 'existing_test_icon_helper');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    $helper = 'existing_test_icon_helper';

    expect($helper('heroicons:clock'))->toBe('existing-helper');
});

it('skips helper registration when inline rendering is disabled', function () {
    config()->set('iconify-api.inline.enabled', false);
    config()->set('iconify-api.inline.helper.name', 'inline_disabled_helper');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(inline_disabled_helper('heroicons:clock'))->toBe('inline-disabled-helper');
});
