<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

/**
 * Point icons_location at a temp dir holding one @iconify-json set with an unsafe icon.
 *
 * @return string The set's unique prefix.
 */
function installUnsafeSet(): string
{
    $prefix = 'unsafe-'.bin2hex(random_bytes(4));
    $base = sys_get_temp_dir().'/iconify-api-unsafe-'.uniqid('', true);
    mkdir("{$base}/@iconify-json/{$prefix}", 0777, true);

    file_put_contents("{$base}/@iconify-json/{$prefix}/icons.json", json_encode([
        'prefix' => $prefix,
        'lastModified' => 1,
        'icons' => ['bad' => ['body' => '<path onload="alert(1)" d="M0 0h24v24H0z"/>']],
        'aliases' => [],
    ], JSON_THROW_ON_ERROR));

    config()->set('iconify-api.icons_location', $base);

    return $prefix;
}

it('renders an unsafe body while reject_unsafe_bodies is off', function () {
    // Characterization: today's behaviour. Passes before the change.
    $prefix = installUnsafeSet();

    expect(LaravelIconifyApi::icon("{$prefix}:bad"))->toContain('onload=');
});

it('renders nothing for an unsafe body while reject_unsafe_bodies is on', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    $prefix = installUnsafeSet();

    expect(LaravelIconifyApi::icon("{$prefix}:bad"))->toBe('');
});

it('stops serving a cached unsafe body as soon as reject_unsafe_bodies is turned on', function () {
    $prefix = installUnsafeSet();

    // Caches the found body under the switch-off key.
    expect(LaravelIconifyApi::icon("{$prefix}:bad"))->toContain('onload=');

    config()->set('iconify-api.reject_unsafe_bodies', true);

    expect(LaravelIconifyApi::icon("{$prefix}:bad"))->toBe('');
});
