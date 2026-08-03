<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\IconifyDirective;

it('can render the directive', function () {
    config()->set('app.url', 'http://localhost');
    $directive = new IconifyDirective;
    $rendered = $directive->render();

    expect($rendered)->toBeString();
    expect($rendered)->toContain('window.IconifyProviders');

    $providers = decodeIconifyProviders($rendered);

    expect($providers)->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
    ]);
});

it('can render the directive with custom providers', function () {
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
    $directive = new IconifyDirective;
    $rendered = $directive->render();

    expect($rendered)->toBeString();
    expect($rendered)->toContain('window.IconifyProviders');

    $providers = decodeIconifyProviders($rendered);

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

it('renders with no custom providers when the config value is explicitly null', function () {
    config()->set('iconify-api.custom_providers', null);

    $rendered = (new IconifyDirective)->render();

    $providers = decodeIconifyProviders($rendered);

    expect($providers)->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
    ]);
});

it('rejects a custom providers config that is not an array', function () {
    config()->set('iconify-api.custom_providers', 'not-an-array');

    expect(fn () => (new IconifyDirective)->render())->toThrow(InvalidArgumentException::class);
});
