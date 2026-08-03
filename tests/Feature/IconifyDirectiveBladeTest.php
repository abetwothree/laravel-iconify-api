<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

function renderIconifyDirective(): string
{
    $nonce = uniqid('iconify-', true);

    return Blade::render("@iconify\n{{-- {$nonce} --}}", [], true);
}

it('can render the iconify directive', function () {
    $directive = renderIconifyDirective();

    expect($directive)->toBeString()
        ->toContain('window.IconifyProviders');

    $providers = decodeIconifyProviders($directive);

    expect($providers)->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
    ]);
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

    $directive = renderIconifyDirective();

    expect($directive)->toBeString()
        ->toContain('window.IconifyProviders');

    $providers = decodeIconifyProviders($directive);

    expect($providers)->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
        'custom' => [
            'resources' => ['http://example.com'],
        ],
        'awesome-custom' => [
            'resources' => ['http://example.com', 'http://test.com'],
            'rotate' => 1000,
        ],
    ]);
});
