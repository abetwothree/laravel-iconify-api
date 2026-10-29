<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\IconifyDirective;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;

/**
 * A Blade template holding the directive, made unique so it compiles to a view of its own.
 *
 * Blade::render() names the view after a hash of the template, so without the comment
 * one test would reuse the view another test compiled.
 */
function uniqueIconifyTemplate(string $directive = '@iconify'): string
{
    return $directive."\n{{-- ".uniqid('iconify-', true).' --}}';
}

/**
 * Render the directive once through the real Blade compiler, then delete the view.
 */
function renderIconifyDirective(string $directive = '@iconify'): string
{
    return Blade::render(uniqueIconifyTemplate($directive), [], true);
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

    // Keep the compiled view, so the next render reuses it.
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

it('adds the Vite nonce to the script tag', function () {
    Vite::useCspNonce('abc');

    expect(renderIconifyDirective())->toContain('<script nonce="abc" type="text/javascript">');
});

it('lets a nonce passed to the directive win over the Vite nonce', function () {
    Vite::useCspNonce('from-vite');

    expect(renderIconifyDirective("@iconify('explicit')"))
        ->toContain('<script nonce="explicit" type="text/javascript">')
        ->not->toContain('from-vite');
});

it('prints each request its own nonce from one compiled view', function () {
    $template = uniqueIconifyTemplate();

    Vite::useCspNonce('first-request');
    // Keep the compiled view, so the next render reuses it.
    $first = Blade::render($template);

    // Same template, so this render reuses the view the first one compiled.
    Vite::useCspNonce('second-request');
    $second = Blade::render($template, [], true);

    expect($first)->toContain('<script nonce="first-request" type="text/javascript">')
        ->and($second)->toContain('<script nonce="second-request" type="text/javascript">')
        ->and($second)->not->toContain('first-request');
});
