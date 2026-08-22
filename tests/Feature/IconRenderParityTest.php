<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;

it('renders heroicons with the icon set root viewBox', function () {
    $helper = 'icon';
    $svg = $helper('heroicons:clock');

    expect($svg)->toContain('viewBox="0 0 24 24"');
});

it('renders mdi with the icon set root viewBox', function () {
    $helper = 'icon';
    $svg = $helper('mdi:home');

    expect($svg)->toContain('viewBox="0 0 24 24"');
});

it('shares the svg id replacer across container resolutions', function () {
    expect(app(SvgIdReplacer::class))->toBe(app(SvgIdReplacer::class));
});

it('does not repeat svg ids across separate helper calls', function () {
    // Bind a deterministic icon whose body declares an id, so this test always
    // exercises the collision path rather than depending on which icon sets
    // happen to be installed.
    app()->bind(IconFinder::class, function () {
        $finder = Mockery::mock(IconFinder::class);

        $finder->shouldReceive('find')->andReturn([
            'gradient' => [
                'icons' => [
                    'gradient' => [
                        'body' => '<defs><linearGradient id="g1"/></defs><path fill="url(#g1)"/>',
                        'width' => 24,
                        'height' => 24,
                    ],
                ],
                'aliases' => [],
                'defaults' => [],
            ],
        ]);

        return $finder;
    });

    $helper = 'icon';

    $first = $helper('mdi:gradient');
    $second = $helper('mdi:gradient');

    preg_match_all('/\sid="([^"]+)"/', $first, $firstIds);
    preg_match_all('/\sid="([^"]+)"/', $second, $secondIds);

    expect($firstIds[1])->not->toBeEmpty();
    expect($secondIds[1])->not->toBeEmpty();
    expect(array_intersect($firstIds[1], $secondIds[1]))->toBe([]);
});
