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

it('escapes angle brackets so a custom provider url cannot break out of the script tag', function () {
    // `</script>` closes the tag. `<!--<script>` is subtler: it makes the browser
    // ignore the real closing tag and swallow the rest of the page. Escaping only
    // slashes would miss that one.
    $payload = 'https://evil.test/<!--<script></script><img src=x onerror=alert(1)>';

    config()->set('iconify-api.custom_providers', [
        'breakout' => [
            'resources' => [$payload],
        ],
    ]);

    $rendered = (new IconifyDirective)->render();

    expect(substr_count($rendered, '</script>'))->toBe(1)
        ->and($rendered)->not->toContain('<!--')
        ->and($rendered)->not->toContain('<img src=x');

    // Escaping is transport-only, so the URL decodes back to what was configured.
    expect(decodeIconifyProviders($rendered))->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
        'breakout' => [
            'resources' => [$payload],
        ],
    ]);
});

it('rejects a custom providers config that is not an array', function () {
    config()->set('iconify-api.custom_providers', 'not-an-array');

    expect(fn () => (new IconifyDirective)->render())->toThrow(InvalidArgumentException::class);
});
