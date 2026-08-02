<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;

it('parses iconify icon name formats', function () {
    $finder = Mockery::mock(IconFinder::class);
    $renderer = new IconSvgRenderer($finder);

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

    $renderer = new IconSvgRenderer($finder);

    expect($renderer->render('invalidname'))->toBe('');

    $finder->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([]);
    expect($renderer->render('mdi:home'))->toBe('');

    $finder2 = Mockery::mock(IconFinder::class);

    $finder2->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([
        'home' => ['icons' => [], 'aliases' => [], 'defaults' => [], 'not_found' => ['home']],
    ]);

    $renderer2 = new IconSvgRenderer($finder2);
    expect($renderer2->render('mdi:home'))->toBe('');
});

it('covers alias resolution edge cases and transformed output', function () {
    $finder = Mockery::mock(IconFinder::class);

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
            'defaults' => [],
        ],
    ]);

    config()->set('iconify-api.inline.defaults', [
        'class' => 'size-5',
        'width' => '2em',
        'height' => '2em',
    ]);

    $renderer = new IconSvgRenderer($finder);
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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);

    $svgWithProvider = $renderer->render('@custom:mdi:home');
    expect($svgWithProvider)->toContain('class="iconify iconify--custom iconify--mdi"');

    $svgWithoutProvider = $renderer->render('mdi-home');
    expect($svgWithoutProvider)->toContain('class="iconify iconify--mdi"');
});

it('covers unresolved alias parent and non-transform output branch', function () {
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['home'])->once()->andReturn([
        'home' => [
            'icons' => [],
            'aliases' => [
                'home' => ['parent' => 'missing-parent'],
            ],
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    expect($renderer->render('mdi:home'))->toBe('');

    $finder2 = Mockery::mock(IconFinder::class);

    $finder2->shouldReceive('find')->with('mdi', ['flat'])->once()->andReturn([
        'flat' => [
            'icons' => [
                'flat' => [
                    'body' => '<path d="M1 1"/>',
                ],
            ],
            'aliases' => [],
            'defaults' => ['width' => 16, 'height' => 16],
        ],
    ]);

    config()->set('iconify-api.inline.defaults', [
        'class' => '',
    ]);

    $renderer2 = new IconSvgRenderer($finder2);
    $svg = $renderer2->render('mdi:flat', [
        'data-value' => ['array-is-stringified-via-safeString'],
    ]);

    expect($svg)->toContain('<path d="M1 1"/>');
    expect($svg)->not->toContain('<g transform=');
});

it('covers cycle detection, alias dimensions, numeric and fallback parsing branches', function () {
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['loop'])->once()->andReturn([
        'loop' => [
            'icons' => [],
            'aliases' => [
                'loop' => [
                    'parent' => 'loop',
                ],
            ],
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    expect($renderer->render('mdi:loop'))->toBe('');

    $finder2 = Mockery::mock(IconFinder::class);

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
            'defaults' => ['width' => 16, 'height' => 16],
        ],
    ]);

    config()->set('iconify-api.inline.defaults', [
        'data-null' => null,
        'data-false' => false,
        'data-empty' => '',
        'data-keep' => 'ok',
    ]);

    $renderer2 = new IconSvgRenderer($finder2);
    $svg = $renderer2->render('mdi:mixed');

    expect($svg)->toContain('viewBox="0 1 16 48"');
    expect($svg)->toContain('rotate(-90');
    expect($svg)->toContain('data-keep="ok"');
    expect($svg)->not->toContain('data-null=');
    expect($svg)->not->toContain('data-false=');
    expect($svg)->not->toContain('data-empty=');
});

it('matches iconify dimension keywords for auto and unset values', function () {
    $finder = Mockery::mock(IconFinder::class);

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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    $svg = $renderer->render('mdi:aria', [
        'aria-hidden' => 'false',
    ]);

    expect($svg)->not->toContain('aria-hidden=');
});

it('adds xmlns:xlink when svg body references xlink namespace', function () {
    $finder = Mockery::mock(IconFinder::class);

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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    $svg = $renderer->render('mdi:xlink');

    expect($svg)->toContain('xmlns:xlink="http://www.w3.org/1999/xlink"');
});

it('replaces svg ids to avoid collisions across renders', function () {
    $finder = Mockery::mock(IconFinder::class);

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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);

    $svgFirst = $renderer->render('mdi:ids');
    $svgSecond = $renderer->render('mdi:ids');

    expect($svgFirst)->toContain('id="test"');
    expect($svgFirst)->toContain('xlink:href="#test"');
    expect($svgSecond)->toContain('id="test1"');
    expect($svgSecond)->toContain('xlink:href="#test1"');
});

it('covers remaining parser and helper branches', function () {
    $finder = Mockery::mock(IconFinder::class);
    $renderer = new IconSvgRenderer($finder);

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

    // Only keys the SVG builder reads are handed to it.
    expect($customisations)->not->toHaveKey('inline');
    expect($customisations)->not->toHaveKey('flip');

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

    $safeString = new ReflectionMethod($renderer, 'safeString');
    $safeString->setAccessible(true);
    expect($safeString->invoke($renderer, true, ''))->toBe('1');
});

it('maps ariaHidden to aria-hidden and removes default when false', function () {
    $finder = Mockery::mock(IconFinder::class);

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
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    $svg = $renderer->render('mdi:aria-camel', [
        'ariaHidden' => false,
    ]);

    expect($svg)->not->toContain('aria-hidden=');
    expect($svg)->not->toContain('ariaHidden=');
});

it('removes the aria-hidden default when the option is explicitly null', function () {
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['aria-null'])->once()->andReturn([
        'aria-null' => [
            'icons' => ['aria-null' => ['body' => '<path d="M0 0"/>', 'width' => 24, 'height' => 24]],
            'aliases' => [],
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    $svg = $renderer->render('mdi:aria-null', ['aria-hidden' => null]);

    expect($svg)->not->toContain('aria-hidden=');
});

it('removes the aria-hidden default when ariaHidden option is explicitly null', function () {
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['aria-camel-null'])->once()->andReturn([
        'aria-camel-null' => [
            'icons' => ['aria-camel-null' => ['body' => '<path d="M0 0"/>', 'width' => 24, 'height' => 24]],
            'aliases' => [],
            'defaults' => [],
        ],
    ]);

    $renderer = new IconSvgRenderer($finder);
    $svg = $renderer->render('mdi:aria-camel-null', ['ariaHidden' => null]);

    expect($svg)->not->toContain('aria-hidden=');
});

function makeParityRenderer(string $name): IconSvgRenderer
{
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', [$name])->once()->andReturn([
        $name => [
            'icons' => [$name => ['body' => '<path d="M0 0"/>', 'width' => 24, 'height' => 24]],
            'aliases' => [],
            'defaults' => [],
        ],
    ]);

    return new IconSvgRenderer($finder);
}

it('renders inline as a vertical align style', function () {
    $svg = makeParityRenderer('inline-icon')->render('mdi:inline-icon', ['inline' => true]);

    expect($svg)->toContain('style="vertical-align: -0.125em;"');
    expect($svg)->not->toContain('inline=');
});

it('folds color into the style attribute', function () {
    $svg = makeParityRenderer('color-icon')->render('mdi:color-icon', ['color' => 'red']);

    expect($svg)->toContain('style="color: red;"');
    expect($svg)->not->toContain('color="red"');
});

it('orders color then vertical align then the user style', function () {
    $svg = makeParityRenderer('style-order')->render('mdi:style-order', [
        'color' => 'red',
        'inline' => true,
        'style' => 'color: blue;',
    ]);

    expect($svg)->toContain('style="color: red; vertical-align: -0.125em; color: blue;"');
});

it('keeps a user style untouched when there is no color or inline', function () {
    $svg = makeParityRenderer('plain-style')->render('mdi:plain-style', ['style' => 'opacity: 0.5;']);

    expect($svg)->toContain('style="opacity: 0.5;"');
});

it('swallows framework control props instead of emitting them', function () {
    $svg = makeParityRenderer('ignored')->render('mdi:ignored', [
        'mode' => 'mask',
        'ssr' => true,
        'icon' => 'mdi:other',
        'onLoad' => 'handler',
        'fallback' => 'x',
        'customise' => 'y',
        'children' => 'z',
        '_ref' => 'r',
        'data-keep' => 'yes',
    ]);

    expect($svg)->not->toContain('mode=');
    expect($svg)->not->toContain('ssr=');
    expect($svg)->not->toContain('icon=');
    expect($svg)->not->toContain('onLoad=');
    expect($svg)->not->toContain('fallback=');
    expect($svg)->not->toContain('customise=');
    expect($svg)->not->toContain('children=');
    expect($svg)->not->toContain('_ref=');
    expect($svg)->toContain('data-keep="yes"');
});

it('drops a color value that injects a second css declaration via a semicolon', function () {
    $svg = makeParityRenderer('color-injection-semicolon')->render('mdi:color-injection-semicolon', [
        'color' => 'red; background:url(javascript:alert(1))',
    ]);

    expect($svg)->not->toContain('background:url');
    expect($svg)->not->toContain('color:');
});

it('drops a color value that opens a css block via a brace', function () {
    $svg = makeParityRenderer('color-injection-open-brace')->render('mdi:color-injection-open-brace', [
        'color' => 'red{background:url(javascript:alert(1))',
    ]);

    expect($svg)->not->toContain('background:url');
    expect($svg)->not->toContain('color:');
});

it('drops a color value that closes a css block via a brace', function () {
    $svg = makeParityRenderer('color-injection-close-brace')->render('mdi:color-injection-close-brace', [
        'color' => 'red} .evil{background:url(javascript:alert(1))',
    ]);

    expect($svg)->not->toContain('background:url');
    expect($svg)->not->toContain('.evil');
    expect($svg)->not->toContain('color:');
});

it('drops a color value containing a css comment sequence', function () {
    $svg = makeParityRenderer('color-injection-comment')->render('mdi:color-injection-comment', [
        'color' => 'red/*comment*/',
    ]);

    expect($svg)->not->toContain('color:');
});

it('still emits a legitimate rgb color value with parentheses and commas', function () {
    $svg = makeParityRenderer('color-rgb')->render('mdi:color-rgb', [
        'color' => 'rgb(1,2,3)',
    ]);

    expect($svg)->toContain('style="color: rgb(1,2,3);"');
});

it('still emits a legitimate css custom property color value', function () {
    $svg = makeParityRenderer('color-var')->render('mdi:color-var', [
        'color' => 'var(--x)',
    ]);

    expect($svg)->toContain('style="color: var(--x);"');
});

it('ignores a zero width and falls back to 1em', function () {
    $svg = makeParityRenderer('zero-width')->render('mdi:zero-width', ['width' => 0]);

    expect($svg)->toContain('width="1em"');
});

it('ignores an empty string height and falls back to 1em', function () {
    $svg = makeParityRenderer('empty-height')->render('mdi:empty-height', ['height' => '']);

    expect($svg)->toContain('height="1em"');
});

it('keeps a zero written as a string, matching javascript truthiness', function () {
    $svg = makeParityRenderer('string-zero')->render('mdi:string-zero', ['width' => '0']);

    expect($svg)->toContain('width="0"');
});

it('ignores a boolean width', function () {
    $svg = makeParityRenderer('bool-width')->render('mdi:bool-width', ['width' => true]);

    expect($svg)->toContain('width="1em"');
});

it('keeps an explicit null width as the default', function () {
    $svg = makeParityRenderer('null-width')->render('mdi:null-width', ['width' => null]);

    expect($svg)->toContain('width="1em"');
});

it('supports the vue style flip aliases', function (string $attribute, string $expectedTransform) {
    $svg = makeParityRenderer('flip-alias')->render('mdi:flip-alias', [$attribute => true]);

    expect($svg)->toContain($expectedTransform);
    expect($svg)->not->toContain($attribute.'=');
})->with([
    ['horizontal-flip', 'scale(-1 1)'],
    ['h-flip', 'scale(-1 1)'],
    ['horizontalFlip', 'scale(-1 1)'],
    ['vertical-flip', 'scale(1 -1)'],
    ['v-flip', 'scale(1 -1)'],
    ['verticalFlip', 'scale(1 -1)'],
]);

it('removes the aria-hidden default when either spelling opts out', function (array $options) {
    $svg = makeParityRenderer('aria-compose-'.md5(serialize($options)))
        ->render('mdi:aria-compose-'.md5(serialize($options)), $options);

    expect($svg)
        ->not->toContain('aria-hidden=')
        ->not->toContain('ariaHidden=');
})->with([
    'camel wins over kebab' => [['aria-hidden' => false, 'ariaHidden' => true]],
    'kebab wins over camel' => [['ariaHidden' => false, 'aria-hidden' => true]],
    'both opt out' => [['ariaHidden' => false, 'aria-hidden' => false]],
]);

it('keeps the aria-hidden default when both spellings are true', function () {
    $svg = makeParityRenderer('aria-compose-true')->render('mdi:aria-compose-true', [
        'aria-hidden' => true,
        'ariaHidden' => 'true',
    ]);

    expect($svg)
        ->toContain('aria-hidden="true"')
        ->not->toContain('ariaHidden=');
});

it('ignores a rotate that is not a whole number', function (float $rotate) {
    $svg = makeParityRenderer('rotate-float-'.md5((string) $rotate))
        ->render('mdi:rotate-float-'.md5((string) $rotate), ['rotate' => $rotate]);

    expect($svg)
        ->not->toContain('<g transform=')
        ->not->toContain('rotate(');
})->with([
    [1.5],
    [0.5],
    [3.7],
    [-1.5],
]);

it('applies a rotate written as a whole float', function (float $rotate, string $expected) {
    $svg = makeParityRenderer('rotate-whole-'.md5((string) $rotate))
        ->render('mdi:rotate-whole-'.md5((string) $rotate), ['rotate' => $rotate]);

    expect($svg)->toContain($expected);
})->with([
    [2.0, 'rotate(180'],
    [-1.0, 'rotate(-90'],
]);

it('skips an attribute name that smuggles a second attribute', function () {
    $svg = makeParityRenderer('attr-name-injection')->render('mdi:attr-name-injection', [
        'x onload=alert(1)' => 'v',
        'data-keep' => 'yes',
    ]);

    expect($svg)
        ->not->toContain('onload')
        ->not->toContain('alert(1)')
        ->toContain('data-keep="yes"');
});

it('renders a well-formed onclick key while still skipping a malformed name', function () {
    $svg = makeParityRenderer('attr-name-onclick')->render('mdi:attr-name-onclick', [
        'onclick' => 'alert(1)',
        'x onload=alert(1)' => 'v',
    ]);

    expect($svg)
        ->toContain('onclick="alert(1)"')
        ->not->toContain('x onload')
        ->not->toContain('="v"');
});

it('skips attribute names that are not valid xml names', function (string $name) {
    $svg = makeParityRenderer('attr-name-'.md5($name))->render('mdi:attr-name-'.md5($name), [
        $name => 'v',
    ]);

    expect($svg)->not->toContain('="v"');
})->with([
    ['data attr'],
    ['1data'],
    ['-data'],
    ['data"attr'],
    ['data<attr'],
    ['data/attr'],
    ['data=attr'],
    [''],
]);

it('keeps valid xml attribute names', function (string $name) {
    $svg = makeParityRenderer('attr-ok-'.md5($name))->render('mdi:attr-ok-'.md5($name), [
        $name => 'v',
    ]);

    expect($svg)->toContain($name.'="v"');
})->with([
    ['data-slot'],
    ['xml:lang'],
    ['_private'],
    ['data.attr'],
    ['tabindex'],
    ['x-on:click'],
    ['wire:model'],
    ['v-bind:foo'],
    [':class'],
    ['x-data'],
    ['data-foo'],
    ['xmlns:xlink'],
    ['@click'],
    ['@submit.prevent'],
]);

it('ignores a falsy flip alias', function () {
    $svg = makeParityRenderer('flip-false')->render('mdi:flip-false', ['h-flip' => false]);

    expect($svg)->not->toContain('<g transform=');
    expect($svg)->not->toContain('h-flip=');
});

it('does not fatal when the icon set root declares a zero dimension', function () {
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['zero-box'])->once()->andReturn([
        'zero-box' => [
            'icons' => ['zero-box' => ['body' => '<path d="M0 0"/>']],
            'aliases' => [],
            'defaults' => ['width' => 24, 'height' => 0],
        ],
    ]);

    $svg = (new IconSvgRenderer($finder))->render('mdi:zero-box');

    expect($svg)
        ->toContain('viewBox="0 0 24 0"')
        ->toContain('width="1em"')
        ->toContain('height="1em"');
});

it('renders a lowercase onload option like any other attribute', function () {
    $svg = makeParityRenderer('onload')->render('mdi:onload', ['onload' => 'init()']);

    expect($svg)->toContain('onload="init()"');
});

it('inherits icon-set root defaults when the finder supplies them', function () {
    $finder = Mockery::mock(IconFinder::class);

    $finder->shouldReceive('find')->with('mdi', ['root-default-dims'])->once()->andReturn([
        'root-default-dims' => [
            'icons' => ['root-default-dims' => ['body' => '<path d="M0 0"/>']],
            'aliases' => [],
            'defaults' => ['width' => 32, 'height' => 32],
        ],
    ]);

    $svg = (new IconSvgRenderer($finder))->render('mdi:root-default-dims');

    expect($svg)->toContain('viewBox="0 0 32 32"');
});

it('degrades to the 16x16 fallback instead of erroring when a custom finder omits defaults', function () {
    // A stub `IconFinderContract` implementation — not a mock — standing in for a
    // custom finder written before icon-set root defaults existed. It returns an
    // entry with no `defaults` key at all, which used to be an unguarded array
    // access away from an undefined-array-key warning and a TypeError.
    $finder = new class implements IconFinder
    {
        /** {@inheritDoc} */
        public function find(string $prefix, array $icons): array
        {
            return [
                'no-defaults-key' => [
                    'icons' => ['no-defaults-key' => ['body' => '<path d="M0 0"/>']],
                    'aliases' => [],
                ],
            ];
        }
    };

    $svg = (new IconSvgRenderer($finder))->render('mdi:no-defaults-key');

    // Same icon body as the previous test, minus the `defaults` key: the icon-set
    // root defaults (32x32) that a finder honouring the key would have supplied are
    // gone, so it renders with the pre-branch 16x16 fallback rather than throwing.
    expect($svg)->toContain('viewBox="0 0 16 16"');
});
