<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoSummaryFinder as IconSetInfoSummaryFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinderCached;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApiServiceProvider;
use Illuminate\Support\Facades\View;

it('covers boot short-circuit when package is disabled', function () {
    config()->set('iconify-api.enabled', false);

    $provider = app()->getProvider(LaravelIconifyApiServiceProvider::class);

    expect($provider->boot())->toBe($provider);
});

it('covers service bindings without explicit cache store', function () {
    config()->set('iconify-api.cache_store', null);
    config()->set('cache.default', '');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(app(IconSetsFileFinderContract::class))->toBeInstanceOf(IconSetsFileFinder::class);
    expect(app(IconFinderContract::class))->toBeInstanceOf(IconFinder::class);
    expect(app(IconSetInfoSummaryFinderContract::class))->toBeInstanceOf(IconSetInfoSummaryFinder::class);
    expect(app(IconSetInfoFinderContract::class))->toBeInstanceOf(IconSetInfoFinder::class);
});

it('covers service bindings with cache store', function () {
    config()->set('iconify-api.cache_store', 'array');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(app(IconSetsFileFinderContract::class))->toBeInstanceOf(IconSetsFileFinderCached::class);
    expect(app(IconFinderContract::class))->toBeInstanceOf(IconFinderCached::class);
    expect(app(IconSetInfoSummaryFinderContract::class))->toBeInstanceOf(IconSetInfoSummaryFinderCached::class);
    expect(app(IconSetInfoFinderContract::class))->toBeInstanceOf(IconSetInfoFinderCached::class);
});

it('covers invalid helper name branch for helper registration', function () {
    config()->set('iconify-api.inline.enabled', true);
    config()->set('iconify-api.inline.helper.enabled', true);
    config()->set('iconify-api.inline.helper.name', 'invalid-name');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(function_exists('invalid-name'))->toBeFalse();
});

it('covers dotted fallback component collision lookup', function () {
    config()->set('iconify-api.inline.enabled', true);
    config()->set('iconify-api.inline.component.enabled', true);
    config()->set('iconify-api.inline.component.name', 'foo-bar-baz');

    View::shouldReceive('exists')
        ->once()
        ->with('components.foo-bar-baz')
        ->andReturn(false);

    View::shouldReceive('exists')
        ->once()
        ->with('components.foo.bar.baz')
        ->andReturn(true);

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    /** @var array<string, string> $aliases */
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect(array_key_exists('foo-bar-baz', $aliases))->toBeFalse();
});

it('covers top-level helper disabled branch', function () {
    config()->set('iconify-api.inline.enabled', true);
    config()->set('iconify-api.inline.helper.enabled', false);
    config()->set('iconify-api.inline.helper.name', 'inline_disabled_provider_helper');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(function_exists('inline_disabled_provider_helper'))->toBeFalse();
});

it('covers top-level component disabled branch', function () {
    config()->set('iconify-api.inline.enabled', true);
    config()->set('iconify-api.inline.component.enabled', false);
    config()->set('iconify-api.inline.component.name', 'inline-disabled-provider-component');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    /** @var array<string, string> $aliases */
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect(array_key_exists('inline-disabled-provider-component', $aliases))->toBeFalse();
});

it('covers blank helper name validation branch', function () {
    config()->set('iconify-api.inline.enabled', true);
    config()->set('iconify-api.inline.helper.enabled', true);
    config()->set('iconify-api.inline.helper.name', '');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    // If no exception occurs, the blank-name guard branch was safely handled.
    expect(true)->toBeTrue();
});
