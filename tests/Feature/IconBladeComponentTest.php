<?php

use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApiServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\View\Component;

class ExistingIconComponent extends Component
{
    public function render(): Closure
    {
        return fn (): string => '<span>existing-component</span>';
    }
}

it('renders svg with icon blade component', function () {
    $svg = Blade::render('<x-icon name="heroicons:clock" />', [], true);

    expect($svg)
        ->toBeString()
        ->toContain('<svg ')
        ->toContain('viewBox=')
        ->toContain('</svg>');
});

it('forwards custom attributes and class to blade component', function () {
    $svg = Blade::render('<x-icon name="heroicons:clock" class="w-6 h-6" data-slot="icon" />', [], true);

    expect($svg)
        ->toContain('class="iconify iconify--heroicons w-6 h-6"')
        ->toContain('data-slot="icon"');
});

it('merges configured default class with component class', function () {
    config()->set('iconify-api.inline.defaults.class', 'size-5');

    $svg = Blade::render('<x-icon name="heroicons:clock" class="w-6" />', [], true);

    expect($svg)->toContain('class="iconify iconify--heroicons size-5 w-6"');
});

it('applies arbitrary configured defaults to blade component output', function () {
    config()->set('iconify-api.inline.defaults', [
        'class' => 'size-5',
        'data-source' => 'default',
        'style' => 'color: red;',
    ]);

    $svg = Blade::render('<x-icon name="heroicons:clock" />', [], true);

    expect($svg)
        ->toContain('class="iconify iconify--heroicons size-5"')
        ->toContain('data-source="default"')
        ->toContain('style="color: red;"');
});

it('allows blade attributes to override configured defaults while merging class', function () {
    config()->set('iconify-api.inline.defaults', [
        'class' => 'size-5',
        'data-source' => 'default',
        'style' => 'color: red;',
    ]);

    $svg = Blade::render('<x-icon name="heroicons:clock" class="w-6" data-source="override" data-slot="icon" style="color: blue;" />', [], true);

    expect($svg)
        ->toContain('class="iconify iconify--heroicons size-5 w-6"')
        ->toContain('data-source="override"')
        ->toContain('data-slot="icon"')
        ->toContain('style="color: blue;"');
});

it('registers blade component under a custom alias', function () {
    config()->set('iconify-api.inline.component.name', 'iconify-icon');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    $svg = Blade::render('<x-iconify-icon name="heroicons:clock" />', [], true);

    expect($svg)->toContain('<svg ');
});

it('does not override an existing blade component alias', function () {
    Blade::component(ExistingIconComponent::class, 'existing-icon');

    config()->set('iconify-api.inline.component.name', 'existing-icon');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    /** @var array<string, string> $aliases */
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect($aliases['existing-icon'])->toBe(ExistingIconComponent::class);
});

it('skips component registration when inline rendering is disabled', function () {
    Blade::component(ExistingIconComponent::class, 'inline-disabled-icon');

    config()->set('iconify-api.inline.enabled', false);
    config()->set('iconify-api.inline.component.name', 'inline-disabled-icon');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    /** @var array<string, string> $aliases */
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect($aliases['inline-disabled-icon'])->toBe(ExistingIconComponent::class);
});

it('does not override an existing anonymous component with a hyphenated name', function () {
    View::shouldReceive('exists')
        ->once()
        ->with('components.existing-icon')
        ->andReturn(true);

    config()->set('iconify-api.inline.component.name', 'existing-icon');
    app()->register(LaravelIconifyApiServiceProvider::class, force: true);

    /** @var array<string, string> $aliases */
    $aliases = app('blade.compiler')->getClassComponentAliases();

    expect(array_key_exists('existing-icon', $aliases))->toBeFalse();
});

it('supports flip aliases as blade attributes', function () {
    $svg = Blade::render('<x-icon name="heroicons:clock" h-flip="true" />', [], true);

    expect($svg)->toContain('scale(-1 1)');
    expect($svg)->not->toContain('h-flip=');
});
