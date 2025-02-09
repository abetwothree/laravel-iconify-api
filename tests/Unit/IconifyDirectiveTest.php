<?php

use AbeTwoThree\LaravelIconifyApi\IconifyDirective;

it('can render the directive', function () {
    config()->set('app.url', 'http://localhost');
    $directive = new IconifyDirective();
    $rendered = $directive->render();

    expect($rendered)->toBeString();
    expect($rendered)->toContain('window.IconifyProviders');
    expect($rendered)->toContain('IconifyProviders = {');
    expect($rendered)->toContain('resources: [');
    expect($rendered)->toContain('}');
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
    $directive = new IconifyDirective();
    $rendered = $directive->render();

    expect($rendered)->toBeString();
    expect($rendered)->toContain('window.IconifyProviders');
    expect($rendered)->toContain('IconifyProviders = {');
    expect($rendered)->toContain('resources: [');
    expect($rendered)->toContain('http://example.com');
    expect($rendered)->toContain('http://test.com');
    expect($rendered)->toContain('rotate');
    expect($rendered)->toContain('}');
});
