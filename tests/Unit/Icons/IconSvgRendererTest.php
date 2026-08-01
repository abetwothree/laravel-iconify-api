<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;

it('covers render early returns for invalid names and not-found icons', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $renderer = new IconSvgRenderer($finder, $infoFinder);

    expect($renderer->render('invalid-name'))->toBe('');

    $finder->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([]);
    expect($renderer->render('mdi:home'))->toBe('');

    $finder2 = Mockery::mock(IconFinder::class);
    $infoFinder2 = Mockery::mock(IconSetInfoFinder::class);

    $finder2->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([
        'home' => ['icons' => [], 'aliases' => [], 'not_found' => ['home']],
    ]);

    $renderer2 = new IconSvgRenderer($finder2, $infoFinder2);
    expect($renderer2->render('mdi:home'))->toBe('');
});

it('covers alias resolution edge cases and transformed output', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([
        'home' => [
            'icons' => [
                'base' => [
                    'body' => '<path d="M0 0"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [
                'home' => [
                    'parent' => 'base',
                    'hFlip' => true,
                    'vFlip' => true,
                    'rotate' => 1,
                ],
            ],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    config()->set('iconify-api.inline.defaults', [
        'class' => 'size-5',
        'width' => '2em',
        'height' => '2em',
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    $svg = $renderer->render('mdi:home', [
        'class' => 'w-6',
        'width' => '32',
        'height' => '40',
    ]);

    expect($svg)->toContain('<g transform=');
    expect($svg)->toContain('rotate(90');
    expect($svg)->toContain('class="size-5 w-6"');
    expect($svg)->toContain('width="32"');
    expect($svg)->toContain('height="40"');
});

it('covers unresolved alias parent and non-transform output branch', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([
        'home' => [
            'icons' => [],
            'aliases' => [
                'home' => ['parent' => 'missing-parent'],
            ],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    expect($renderer->render('mdi:home'))->toBe('');

    $finder2 = Mockery::mock(IconFinder::class);
    $infoFinder2 = Mockery::mock(IconSetInfoFinder::class);

    $finder2->shouldReceive('find')->with('mdi', ['flat'])->once()->andReturn([
        'flat' => [
            'icons' => [
                'flat' => [
                    'body' => '<path d="M1 1"/>',
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder2->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    config()->set('iconify-api.inline.defaults', [
        'class' => '',
    ]);

    $renderer2 = new IconSvgRenderer($finder2, $infoFinder2);
    $svg = $renderer2->render('mdi:flat', [
        'data-value' => ['array-is-stringified-via-safeString'],
    ]);

    expect($svg)->toContain('<path d="M1 1"/>');
    expect($svg)->not->toContain('<g transform=');
});

it('covers cycle detection, alias dimensions, numeric and fallback parsing branches', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['loop'])->once()->andReturn([
        'loop' => [
            'icons' => [],
            'aliases' => [
                'loop' => [
                    'parent' => 'loop',
                ],
            ],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    expect($renderer->render('mdi:loop'))->toBe('');

    $finder2 = Mockery::mock(IconFinder::class);
    $infoFinder2 = Mockery::mock(IconSetInfoFinder::class);

    $finder2->shouldReceive('find')->with('mdi', ['mixed'])->once()->andReturn([
        'mixed' => [
            'icons' => [
                'base' => [
                    'body' => '<path d="M2 2"/>',
                    'width' => '48',
                    'height' => [],
                    'rotate' => [],
                ],
            ],
            'aliases' => [
                'mixed' => [
                    'parent' => 'base',
                    'left' => 1,
                    'rotate' => -1,
                ],
            ],
        ],
    ]);

    $infoFinder2->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    config()->set('iconify-api.inline.defaults', [
        'data-null' => null,
        'data-false' => false,
        'data-empty' => '',
        'data-keep' => 'ok',
    ]);

    $renderer2 = new IconSvgRenderer($finder2, $infoFinder2);
    $svg = $renderer2->render('mdi:mixed');

    expect($svg)->toContain('viewBox="1 0 48 16"');
    expect($svg)->toContain('rotate(270');
    expect($svg)->toContain('data-keep="ok"');
    expect($svg)->not->toContain('data-null=');
    expect($svg)->not->toContain('data-false=');
    expect($svg)->not->toContain('data-empty=');
});

it('covers protected parse and safe helpers via reflection', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);
    $renderer = new IconSvgRenderer($finder, $infoFinder);

    $parse = new ReflectionMethod($renderer, 'parseRotateValue');
    $parse->setAccessible(true);

    expect($parse->invoke($renderer, '2'))->toBe(2);
    expect($parse->invoke($renderer, '180deg'))->toBe(2);
    expect($parse->invoke($renderer, 'bad-rotate'))->toBe(0);

    $safeRotate = new ReflectionMethod($renderer, 'safeRotateValue');
    $safeRotate->setAccessible(true);
    expect($safeRotate->invoke($renderer, ['bad']))->toBe(0);

    $safeInt = new ReflectionMethod($renderer, 'safeInt');
    $safeInt->setAccessible(true);
    expect($safeInt->invoke($renderer, '42', 9))->toBe(42);
    expect($safeInt->invoke($renderer, ['bad'], 9))->toBe(9);
});
