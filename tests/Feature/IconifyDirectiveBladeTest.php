<?php

use Illuminate\Support\Facades\Blade;

it('can render the iconify directive', function () {
    $directive = Blade::render('@iconify', [], true);

    expect($directive)->toBeString()
        ->toContain('window.IconifyProviders')
        ->toContain('IconifyProviders = {')
        ->toContain('resources: [')
        ->toContain('}');
});

it('can render the iconify directive with custom providers', function () {
    config()->set('app.url', 'http://localhost');
    config()->set('iconify-api.custom_providers', [
        'custom' => [
            'resources' => [
                'http://example.com',
            ],
        ],
        'awesome-custom' => [
            'resources' => [
                'http://example.com',
                'http://test.com',
            ],
            'rotate' => 1000,
        ],
    ]);

    $directive = Blade::render('@iconify', [], true);

    expect($directive)->toBeString()
        ->toContain('window.IconifyProviders')
        ->toContain('IconifyProviders = {')
        ->toContain('resources: [')
        ->toContain('http://example.com')
        ->toContain('http://test.com')
        ->toContain('rotate')
        ->toContain('}');
});
