<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;

it('renders an svg through the facade', function () {
    expect(LaravelIconifyApi::icon('heroicons:clock'))
        ->toContain('<svg ')
        ->toContain('viewBox="0 0 24 24"')
        ->toContain('</svg>');
});

it('renders the same svg as the renderer for the same options', function () {
    $options = ['class' => 'w-6', 'width' => 24, 'data-slot' => 'icon'];

    expect(LaravelIconifyApi::icon('heroicons:clock', $options))
        ->toBe(app(IconSvgRenderer::class)->render('heroicons:clock', $options));
});

it('returns an empty string for an unknown icon through the facade', function () {
    expect(LaravelIconifyApi::icon('heroicons:not-an-icon'))->toBe('');
});

it('declares icon() in the facade docblock for analysers without Larastan', function () {
    $docComment = (string) (new ReflectionClass(LaravelIconifyApi::class))->getDocComment();

    expect($docComment)->toContain('@method static string icon(string $name, array<string, mixed> $options = [])');
});
