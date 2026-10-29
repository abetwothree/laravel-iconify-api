<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\IconifyDirective;
use Illuminate\Support\Facades\Vite;

/**
 * The tag render() prints for the default config with no CSP nonce in use.
 *
 * Spelled out in full so any change to the markup has to be deliberate.
 */
function defaultIconifyTag(): string
{
    return implode("\n", [
        '    <script type="text/javascript">',
        '        if(!window.IconifyProviders) {',
        '            window.IconifyProviders = {};',
        '        }',
        '',
        '        IconifyProviders = {',
        '    "": {',
        '        "resources": [',
        '            "/iconify/api"',
        '        ]',
        '    }',
        '};',
        '    </script>',
    ]);
}

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

it('renders the default tag byte for byte', function () {
    expect((new IconifyDirective)->render())->toBe(defaultIconifyTag());
});

it('adds the Vite nonce to the script tag and changes nothing else', function () {
    Vite::useCspNonce('abc');

    expect((new IconifyDirective)->render())
        ->toBe(str_replace('<script type=', '<script nonce="abc" type=', defaultIconifyTag()));
});

it('lets an explicit nonce win over the Vite nonce', function () {
    Vite::useCspNonce('from-vite');

    expect((new IconifyDirective)->render('explicit'))
        ->toContain('<script nonce="explicit" type="text/javascript">')
        ->not->toContain('from-vite');
});

it('escapes the nonce so it cannot break out of the attribute', function () {
    Vite::useCspNonce('"><script>alert(1)</script>');

    $rendered = (new IconifyDirective)->render();

    expect($rendered)
        ->toContain('<script nonce="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;" type="text/javascript">')
        ->and(substr_count($rendered, '<script'))->toBe(1);
});

it('treats a blank nonce as no nonce', function () {
    // nonce="" can never match a CSP nonce-source, so print no attribute at all.
    Vite::useCspNonce('');

    expect((new IconifyDirective)->render())->toBe(defaultIconifyTag());

    Vite::useCspNonce('from-vite');

    expect((new IconifyDirective)->render(''))
        ->toContain('<script nonce="from-vite" type="text/javascript">');
});
