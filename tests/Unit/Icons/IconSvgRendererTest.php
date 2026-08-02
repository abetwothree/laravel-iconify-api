<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;

it('parses iconify icon name formats', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);
    $renderer = new IconSvgRenderer($finder, $infoFinder);

    $parse = new ReflectionMethod($renderer, 'parseIconName');
    $parse->setAccessible(true);

    expect($parse->invoke($renderer, '@custom:mdi:home'))->toBe([
        'provider' => 'custom',
        'prefix' => 'mdi',
        'name' => 'home',
    ]);

    expect($parse->invoke($renderer, 'custom:mdi:home'))->toBe([
        'provider' => 'custom',
        'prefix' => 'mdi',
        'name' => 'home',
    ]);

    expect($parse->invoke($renderer, 'mdi:home'))->toBe([
        'provider' => '',
        'prefix' => 'mdi',
        'name' => 'home',
    ]);

    expect($parse->invoke($renderer, 'mdi-home'))->toBe([
        'provider' => '',
        'prefix' => 'mdi',
        'name' => 'home',
    ]);

    expect($parse->invoke($renderer, 'invalid-name'))->toBe([
        'provider' => '',
        'prefix' => 'invalid',
        'name' => 'name',
    ]);

    expect($parse->invoke($renderer, 'bad'))->toBeNull();
    expect($parse->invoke($renderer, '@bad'))->toBeNull();
    expect($parse->invoke($renderer, '@bad:'))->toBeNull();
    expect($parse->invoke($renderer, ''))->toBeNull();
});

it('covers render early returns for invalid names and not-found icons', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $renderer = new IconSvgRenderer($finder, $infoFinder);

    expect($renderer->render('invalidname'))->toBe('');

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
    expect($svg)->toContain('rotate(-90');
    expect($svg)->toContain('class="iconify iconify--mdi size-5 w-6"');
    expect($svg)->toContain('width="32"');
    expect($svg)->toContain('height="40"');
});

it('adds iconify classes for provider and prefix formats', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['home'])->twice()->andReturn([
        'home' => [
            'icons' => [
                'home' => [
                    'body' => '<path d="M0 0"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->twice()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);

    $svgWithProvider = $renderer->render('@custom:mdi:home');
    expect($svgWithProvider)->toContain('class="iconify iconify--custom iconify--mdi"');

    $svgWithoutProvider = $renderer->render('mdi-home');
    expect($svgWithoutProvider)->toContain('class="iconify iconify--mdi"');
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

    expect($svg)->toContain('viewBox="0 1 16 48"');
    expect($svg)->toContain('rotate(-90');
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
    expect($parse->invoke($renderer, '50%'))->toBe(2);
    expect($parse->invoke($renderer, '45deg'))->toBe(0);
    expect($parse->invoke($renderer, 'bad-rotate'))->toBe(0);
});

it('matches iconify dimension keywords for auto and unset values', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['sized'])->once()->andReturn([
        'sized' => [
            'icons' => [
                'sized' => [
                    'body' => '<path d="M0 0"/>',
                    'width' => 20,
                    'height' => 16,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    $svg = $renderer->render('mdi:sized', [
        'width' => 'auto',
        'height' => 'unset',
    ]);

    expect($svg)->toContain('viewBox="0 0 20 16"');
    expect($svg)->toContain('width="20"');
    expect($svg)->not->toContain('height="');
});

it('adds iconify svg defaults and protects generated viewBox', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['attrs'])->once()->andReturn([
        'attrs' => [
            'icons' => [
                'attrs' => [
                    'body' => '<path d="M0 0"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    $svg = $renderer->render('mdi:attrs', [
        'viewBox' => '0 0 1 1',
    ]);

    expect($svg)->toContain('xmlns="http://www.w3.org/2000/svg"');
    expect($svg)->toContain('aria-hidden="true"');
    expect($svg)->toContain('role="img"');
    expect($svg)->toContain('viewBox="0 0 24 24"');
    expect($svg)->not->toContain('viewBox="0 0 1 1"');
});

it('removes aria-hidden default when explicitly disabled', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['aria'])->once()->andReturn([
        'aria' => [
            'icons' => [
                'aria' => [
                    'body' => '<path d="M0 0"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    $svg = $renderer->render('mdi:aria', [
        'aria-hidden' => 'false',
    ]);

    expect($svg)->not->toContain('aria-hidden=');
});

it('adds xmlns:xlink when svg body references xlink namespace', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['xlink'])->once()->andReturn([
        'xlink' => [
            'icons' => [
                'xlink' => [
                    'body' => '<defs><path id="test1"/></defs><use xlink:href="#test1"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    $svg = $renderer->render('mdi:xlink');

    expect($svg)->toContain('xmlns:xlink="http://www.w3.org/1999/xlink"');
});

it('replaces svg ids to avoid collisions across renders', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['ids'])->twice()->andReturn([
        'ids' => [
            'icons' => [
                'ids' => [
                    'body' => '<defs><path id="test1"/></defs><use xlink:href="#test1"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->twice()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);

    $svgFirst = $renderer->render('mdi:ids');
    $svgSecond = $renderer->render('mdi:ids');

    expect($svgFirst)->toContain('id="test"');
    expect($svgFirst)->toContain('xlink:href="#test"');
    expect($svgSecond)->toContain('id="test1"');
    expect($svgSecond)->toContain('xlink:href="#test1"');
});

it('covers remaining parser and helper branches', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);
    $renderer = new IconSvgRenderer($finder, $infoFinder);

    $parse = new ReflectionMethod($renderer, 'parseIconName');
    $parse->setAccessible(true);

    expect($parse->invoke($renderer, 'a:b:c:d'))->toBeNull();
    expect($parse->invoke($renderer, 'mdi:'))->toBeNull();
    expect($parse->invoke($renderer, '-home'))->toBeNull();
    expect($parse->invoke($renderer, 'mdi-'))->toBeNull();

    $autoClasses = new ReflectionMethod($renderer, 'buildAutomaticClasses');
    $autoClasses->setAccessible(true);
    expect($autoClasses->invoke($renderer, null))->toBe([]);

    $extract = new ReflectionMethod($renderer, 'extractCustomisations');
    $extract->setAccessible(true);
    $customisations = $extract->invoke($renderer, [
        'flip' => 'horizontal, vertical',
        'rotate' => 2,
        'inline' => true,
    ]);

    expect($customisations['hFlip'])->toBeTrue();
    expect($customisations['vFlip'])->toBeTrue();
    expect($customisations['rotate'])->toBe(2);

    config()->set('iconify-api.inline.defaults', [
        'class' => '',
    ]);

    $merge = new ReflectionMethod($renderer, 'mergeDefaultAttributes');
    $merge->setAccessible(true);
    $merged = $merge->invoke($renderer, ['class' => ''], []);
    expect($merged)->not->toHaveKey('class');

    $stringify = new ReflectionMethod($renderer, 'stringifyAttributes');
    $stringify->setAccessible(true);
    $attributeString = $stringify->invoke($renderer, [
        'data-array' => ['value'],
        'data-keep' => 'ok',
    ]);
    expect($attributeString)->toContain('data-array=""');
    expect($attributeString)->toContain('data-keep="ok"');

    $implode = new ReflectionMethod($renderer, 'implodeUniqueClasses');
    $implode->setAccessible(true);
    expect($implode->invoke($renderer, ['', 'dup', 'dup', 'keep']))->toBe('dup keep');

    $parseRotate = new ReflectionMethod($renderer, 'parseRotateValue');
    $parseRotate->setAccessible(true);
    expect($parseRotate->invoke($renderer, '10rad'))->toBe(0);
    expect($parseRotate->invoke($renderer, '.deg'))->toBe(0);

    $safeString = new ReflectionMethod($renderer, 'safeString');
    $safeString->setAccessible(true);
    expect($safeString->invoke($renderer, true, ''))->toBe('1');
});

it('maps ariaHidden to aria-hidden and removes default when false', function () {
    $finder = Mockery::mock(IconFinder::class);
    $infoFinder = Mockery::mock(IconSetInfoFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['aria-camel'])->once()->andReturn([
        'aria-camel' => [
            'icons' => [
                'aria-camel' => [
                    'body' => '<path d="M0 0"/>',
                    'width' => 24,
                    'height' => 24,
                ],
            ],
            'aliases' => [],
        ],
    ]);

    $infoFinder->shouldReceive('find')->with('mdi')->once()->andReturn([
        'prefix' => 'mdi',
        'width' => 16,
        'height' => 16,
    ]);

    $renderer = new IconSvgRenderer($finder, $infoFinder);
    $svg = $renderer->render('mdi:aria-camel', [
        'ariaHidden' => false,
    ]);

    expect($svg)->not->toContain('aria-hidden=');
    expect($svg)->not->toContain('ariaHidden=');
});
