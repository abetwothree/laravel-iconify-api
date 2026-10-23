<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

use function AbeTwoThree\LaravelIconifyApi\icon;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;

it('renders an svg with the namespaced function', function () {
    expect(icon('heroicons:clock'))
        ->toContain('<svg ')
        ->toContain('viewBox="0 0 24 24"');
});

it('is declared by composer autoloading in src/functions.php', function () {
    expect(realpath((string) (new ReflectionFunction('AbeTwoThree\LaravelIconifyApi\icon'))->getFileName()))
        ->toBe(realpath(__DIR__.'/../../src/functions.php'));
});

it('renders the namespaced function through the LaravelIconifyApi facade', function () {
    LaravelIconifyApi::shouldReceive('icon')
        ->once()
        ->with('heroicons:clock', [])
        ->andReturn('<svg>facade</svg>');

    expect(icon('heroicons:clock'))->toBe('<svg>facade</svg>');
});

it('can be imported into a blade view with @use', function () {
    expect(Blade::compileString("@use('function AbeTwoThree\\LaravelIconifyApi\\icon')"))
        ->toContain('use function \AbeTwoThree\LaravelIconifyApi\icon;');

    expect(Blade::render("@use('function AbeTwoThree\\LaravelIconifyApi\\icon')\n{!! icon('heroicons:clock') !!}", [], true))
        ->toContain('<svg ');
})->skip(version_compare(Application::VERSION, '12.11.0', '<'), 'Blade imports functions with @use from Laravel 12.11');
