<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
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
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApiServiceProvider;
use Illuminate\Support\Facades\View;

if (! function_exists('existing_custom_icon_helper')) {
    function existing_custom_icon_helper(): string
    {
        return 'existing';
    }
}

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

it('covers top-level component disabled branch', function () {
    config()->set('iconify-api.inline.enabled', true);
    config()->set('iconify-api.inline.component.enabled', false);
    config()->set('iconify-api.inline.component.name', 'inline-disabled-provider-component');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    /** @var array<string, string> $aliases */
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect(array_key_exists('inline-disabled-provider-component', $aliases))->toBeFalse();
});

it('registers the svg support services as singletons', function () {
    expect(app(SvgIdReplacer::class))
        ->toBe(app(SvgIdReplacer::class));

    expect(app(IconifySvgBuilder::class))
        ->toBe(app(IconifySvgBuilder::class));
});

it('binds the cached finders when the cache store name is falsy as a string', function () {
    config()->set('cache.stores.0', ['driver' => 'array']);
    config()->set('iconify-api.cache_store', '0');

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(app(IconFinderContract::class))->toBeInstanceOf(IconFinderCached::class);
});

it('throws when the removed helper config names a helper nothing declares', function () {
    config()->set('iconify-api.inline.helper', ['enabled' => true, 'name' => 'missing_custom_icon_helper']);

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->toThrow(RuntimeException::class, 'The iconify-api.inline.helper config was removed in 2.0, so missing_custom_icon_helper() is no longer declared. Declare missing_custom_icon_helper() yourself or call icon(), then remove inline.helper from config/iconify-api.php. See https://github.com/abetwothree/laravel-iconify-api#using-a-different-helper-name');
});

it('deprecates a leftover default helper config', function () {
    $this->withoutDeprecationHandling();
    config()->set('iconify-api.inline.helper', ['enabled' => true, 'name' => 'icon']);

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->toThrow(ErrorException::class, 'The iconify-api.inline.helper config was removed in 2.0 and is ignored; icon() is always declared. Remove inline.helper from config/iconify-api.php. See https://github.com/abetwothree/laravel-iconify-api#upgrading-from-1x-to-20');
});

it('keeps booting with a leftover default helper config', function () {
    config()->set('iconify-api.inline.helper', ['enabled' => true, 'name' => 'icon']);

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(icon('heroicons:clock'))->toContain('<svg ');
});

it('only deprecates the removed helper config when the renamed helper is already declared', function () {
    $this->withoutDeprecationHandling();
    config()->set('iconify-api.inline.helper', ['enabled' => true, 'name' => 'existing_custom_icon_helper']);

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->toThrow(ErrorException::class, 'iconify-api.inline.helper config was removed in 2.0 and is ignored');
});

it('only deprecates a disabled helper config', function () {
    $this->withoutDeprecationHandling();
    config()->set('iconify-api.inline.helper', ['enabled' => false, 'name' => 'missing_custom_icon_helper']);

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->toThrow(ErrorException::class, 'iconify-api.inline.helper config was removed in 2.0 and is ignored');
});

it('stays silent when the helper config is absent', function () {
    $this->withoutDeprecationHandling();

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->not->toThrow(Throwable::class);
});

it('keeps the helpers available when inline rendering is disabled', function () {
    config()->set('iconify-api.inline.enabled', false);

    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    expect(icon('heroicons:clock'))->toContain('<svg ')
        ->and(LaravelIconifyApi::icon('heroicons:clock'))->toContain('<svg ');
});

it('only deprecates a renamed helper config when inline rendering is disabled', function () {
    $this->withoutDeprecationHandling();
    config()->set('iconify-api.inline.enabled', false);
    config()->set('iconify-api.inline.helper', ['enabled' => true, 'name' => 'missing_custom_icon_helper']);

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->toThrow(ErrorException::class, 'iconify-api.inline.helper config was removed in 2.0 and is ignored');
});

it('only deprecates a helper config whose name v1 would have skipped', function (string $name) {
    $this->withoutDeprecationHandling();
    config()->set('iconify-api.inline.helper', ['enabled' => true, 'name' => $name]);

    expect(fn () => app()->register(LaravelIconifyApiServiceProvider::class, force: true))
        ->toThrow(ErrorException::class, 'iconify-api.inline.helper config was removed in 2.0 and is ignored');
})->with([
    'blank' => '',
    'invalid php name' => 'my-icon',
]);
