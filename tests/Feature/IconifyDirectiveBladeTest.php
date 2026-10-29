<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\IconifyDirective;
use Illuminate\Support\Facades\Blade;

/**
 * A Blade template holding the directive, made unique so it compiles to a view of its own.
 *
 * Blade::render() names the view after a hash of the template, so without the comment
 * one test would reuse the view another test compiled.
 */
function uniqueIconifyTemplate(): string
{
    return "@iconify\n{{-- ".uniqid('iconify-', true).' --}}';
}

/**
 * Render the directive once through the real Blade compiler, then delete the view.
 */
function renderIconifyDirective(): string
{
    return Blade::render(uniqueIconifyTemplate(), [], true);
}

it('can render the iconify directive', function () {
    $directive = renderIconifyDirective();

    expect($directive)->toBeString()
        ->toContain('window.IconifyProviders');

    $providers = decodeIconifyProviders($directive);

    expect($providers)->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
    ]);
});

it('can render the iconify directive with custom providers', function () {
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

    $directive = renderIconifyDirective();

    expect($directive)->toBeString()
        ->toContain('window.IconifyProviders');

    $providers = decodeIconifyProviders($directive);

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

it('compiles to a call that runs on every render instead of freezing the tag into the view', function () {
    expect(Blade::compileString('@iconify'))
        ->toBe('<?php echo app(\\'.IconifyDirective::class.'::class)->render(); ?>');
});

it('picks up a config change without recompiling the view', function () {
    $template = uniqueIconifyTemplate();

    Blade::render($template);

    config()->set('iconify-api.custom_providers', [
        'custom' => [
            'resources' => ['https://example.com'],
        ],
    ]);

    // Same template, so this render reuses the view the first one compiled.
    expect(decodeIconifyProviders(Blade::render($template, [], true)))->toBe([
        '' => [
            'resources' => ['/iconify/api'],
        ],
        'custom' => [
            'resources' => ['https://example.com'],
        ],
    ]);
});
