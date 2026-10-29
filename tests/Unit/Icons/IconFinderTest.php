<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

it('can find multiple icons', function (
    string $set,
    array $icons
) {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find($set, $icons);

    expect($icons)->each(function ($icon) {
        $icon->toBeArray()
            ->toHaveKeys(['aliases', 'icons'])
            ->aliases->toBeArray()
            ->icons->toBeArray();
    });
})->with([
    ['mdi', ['home', 'account']],
    ['mdi', ['home', 'account', 'abacus']],
    ['mdi', ['account-cash']],
    ['heroicons', ['academic-cap', 'adjustments-vertical']],
    ['heroicons', ['academic-cap', 'adjustments-vertical', 'chart-pie-16-solid']],
    ['heroicons', ['swatch', 'code-solid']],
]);

it('can find a specific icon', function (
    string $set,
    array $icon,
) {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find($set, $icon);

    expect($icons)->toBeArray()
        ->toHaveKeys($icon)
        ->each(function ($icon) {
            $icon->toBeArray()
                ->toHaveKeys(['aliases', 'icons'])
                ->aliases->toBeArray()
                ->icons->toBeArray();
        });
})->with([
    ['mdi', ['home']],
    ['heroicons', ['academic-cap']],
]);

it('returns proper response but fails to find icon', function (
    string $set,
    array $icon,
) {
    $iconFinder = resolve(IconFinder::class);
    $icons = $iconFinder->find($set, $icon);

    expect($icons)
        ->toBeArray()
        ->toHaveKeys($icon)
        ->each(function ($icon) {
            $icon->toBeArray()
                ->toHaveKeys(['aliases', 'icons', 'not_found'])
                ->aliases->toBeArray()
                ->icons->toBeArray()
                ->not_found->toBeArray()
                ->not_found->toHaveCount(1)
                ->not_found->each->toBe('not-an-icon');
        });
})->with([
    ['mdi', ['not-an-icon']],
    ['heroicons', ['not-an-icon']],
]);

it('marks alias as not found when alias parent icon is missing', function () {
    $tempFile = sys_get_temp_dir().'/iconfinder-'.uniqid('', true).'.json';

    $written = file_put_contents($tempFile, json_encode([
        'prefix' => 'test',
        'lastModified' => time(),
        'icons' => [],
        'aliases' => [
            'broken-alias' => [
                'parent' => 'missing-parent',
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    if ($written === false) {
        throw new RuntimeException('Unable to write temporary test file.');
    }

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $finder = new IconFinder($fileFinder);
    $result = $finder->find('test', ['broken-alias']);

    expect($result)->toHaveKey('broken-alias');
    expect($result['broken-alias']['aliases'])->toHaveKey('broken-alias');
    expect($result['broken-alias']['icons'])->toBe([]);
    expect($result['broken-alias']['not_found'] ?? [])->toBe(['broken-alias']);

    @unlink($tempFile);
});

it('resolves multi-level alias chains to parent icon', function () {
    $tempFile = sys_get_temp_dir().'/iconfinder-chain-'.uniqid('', true).'.json';

    $written = file_put_contents($tempFile, json_encode([
        'prefix' => 'test',
        'lastModified' => time(),
        'icons' => [
            'base' => [
                'body' => '<path d="M0 0"/>',
                'width' => 24,
                'height' => 24,
            ],
        ],
        'aliases' => [
            'second' => [
                'parent' => 'base',
                'rotate' => 1,
            ],
            'first' => [
                'parent' => 'second',
                'hFlip' => true,
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    if ($written === false) {
        throw new RuntimeException('Unable to write temporary test file.');
    }

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $finder = new IconFinder($fileFinder);
    $result = $finder->find('test', ['first']);

    expect($result)->toHaveKey('first');
    expect($result['first']['icons'])->toHaveKey('base');
    expect($result['first']['aliases'])->toHaveKeys(['first', 'second']);
    expect($result['first']['not_found'] ?? [])->toBe([]);

    @unlink($tempFile);
});

it('marks alias chain cycles as not found', function () {
    $tempFile = sys_get_temp_dir().'/iconfinder-cycle-'.uniqid('', true).'.json';

    $written = file_put_contents($tempFile, json_encode([
        'prefix' => 'test',
        'lastModified' => time(),
        'icons' => [],
        'aliases' => [
            'a' => ['parent' => 'b'],
            'b' => ['parent' => 'a'],
        ],
    ], JSON_THROW_ON_ERROR));

    if ($written === false) {
        throw new RuntimeException('Unable to write temporary test file.');
    }

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $finder = new IconFinder($fileFinder);
    $result = $finder->find('test', ['a']);

    expect($result)->toHaveKey('a');
    expect($result['a']['icons'])->toBe([]);
    expect($result['a']['aliases'])->toHaveKeys(['a', 'b']);
    expect($result['a']['not_found'] ?? [])->toBe(['a']);

    @unlink($tempFile);
});

it('carries icon set root defaults on every returned icon', function () {
    $iconFinder = resolve(IconFinder::class);
    $result = $iconFinder->find('heroicons', ['clock']);

    expect($result['clock'])->toHaveKey('defaults');
    expect($result['clock']['defaults'])->toBe(['width' => 24, 'height' => 24]);
});

it('carries every legal icon set root default', function () {
    $tempFile = sys_get_temp_dir().'/iconfinder-defaults-'.uniqid('', true).'.json';

    file_put_contents($tempFile, json_encode([
        'prefix' => 'test',
        'lastModified' => time(),
        'left' => 1,
        'top' => 2,
        'width' => 32,
        'height' => 48,
        'rotate' => 1,
        'hFlip' => true,
        'vFlip' => false,
        'icons' => ['base' => ['body' => '<path d="M0 0"/>']],
        'aliases' => [],
    ], JSON_THROW_ON_ERROR));

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $result = (new IconFinder($fileFinder))->find('test', ['base']);

    expect($result['base']['defaults'])->toBe([
        'left' => 1,
        'top' => 2,
        'width' => 32,
        'height' => 48,
        'rotate' => 1,
        'hFlip' => true,
        'vFlip' => false,
    ]);

    @unlink($tempFile);
});

it('returns icon set root defaults verbatim, whatever type the file used', function () {
    // Nothing between json_decode() and the renderer coerces these, so the published
    // TIconDefaults shape has to admit them. See the note on TIconSetData.
    $tempFile = sys_get_temp_dir().'/iconfinder-loose-'.uniqid('', true).'.json';

    file_put_contents($tempFile, '{"prefix":"test","lastModified":1,"left":"-2","width":"24",'
        .'"height":24,"rotate":"1","hFlip":1,"icons":{"base":{"body":"<path d=\"M0 0\"/>"}},"aliases":{}}');

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $result = (new IconFinder($fileFinder))->find('test', ['base']);

    expect($result['base']['defaults'])->toBe([
        'left' => '-2',
        'width' => '24',
        'height' => 24,
        'rotate' => '1',
        'hFlip' => 1,
    ]);

    @unlink($tempFile);
});

it('emits an empty defaults array when the icon set root has none', function () {
    $tempFile = sys_get_temp_dir().'/iconfinder-nodefaults-'.uniqid('', true).'.json';

    file_put_contents($tempFile, json_encode([
        'prefix' => 'test',
        'lastModified' => time(),
        'icons' => ['base' => ['body' => '<path d="M0 0"/>']],
        'aliases' => [],
    ], JSON_THROW_ON_ERROR));

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $result = (new IconFinder($fileFinder))->find('test', ['base']);

    expect($result['base']['defaults'])->toBe([]);

    @unlink($tempFile);
});

/**
 * An IconFinder over a temporary set holding one safe and one unsafe icon, each with an alias.
 *
 * Returns the finder and the temp file path, which the caller deletes.
 *
 * @return array{0: IconFinder, 1: string}
 */
function finderOverUnsafeSet(): array
{
    $path = sys_get_temp_dir().'/iconfinder-unsafe-'.uniqid('', true).'.json';

    file_put_contents($path, json_encode([
        'prefix' => 'test',
        'lastModified' => 1,
        'icons' => [
            'safe' => ['body' => '<path d="M0 0h24v24H0z"/>'],
            'unsafe' => ['body' => '<path onload="alert(1)" d="M0 0h24v24H0z"/>'],
        ],
        'aliases' => [
            'safe-alias' => ['parent' => 'safe'],
            'unsafe-alias' => ['parent' => 'unsafe'],
        ],
    ], JSON_THROW_ON_ERROR));

    $fileFinder = new class($path) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    return [new IconFinder($fileFinder), $path];
}

it('returns an unsafe body unchanged while reject_unsafe_bodies is off', function () {
    // Characterization: the default must keep upstream behaviour. Passes before the change.
    [$finder, $path] = finderOverUnsafeSet();

    $result = $finder->find('test', ['unsafe']);

    expect($result['unsafe']['icons'])->toHaveKey('unsafe')
        ->and($result['unsafe'])->not->toHaveKey('not_found');

    @unlink($path);
});

it('reports an unsafe icon as not found when reject_unsafe_bodies is on', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    [$finder, $path] = finderOverUnsafeSet();

    expect($finder->find('test', ['unsafe'])['unsafe'])->toBe([
        'icons' => [],
        'aliases' => [],
        'defaults' => [],
        'not_found' => ['unsafe'],
    ]);

    @unlink($path);
});

it('reports an alias of an unsafe icon as not found', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    [$finder, $path] = finderOverUnsafeSet();

    expect($finder->find('test', ['unsafe-alias'])['unsafe-alias'])->toBe([
        'icons' => [],
        'aliases' => [],
        'defaults' => [],
        'not_found' => ['unsafe-alias'],
    ]);

    @unlink($path);
});

it('keeps safe icons and their aliases when reject_unsafe_bodies is on', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    [$finder, $path] = finderOverUnsafeSet();

    $result = $finder->find('test', ['safe', 'safe-alias']);

    expect($result['safe']['icons'])->toHaveKey('safe')
        ->and($result['safe-alias']['icons'])->toHaveKey('safe')
        ->and($result['safe'])->not->toHaveKey('not_found')
        ->and($result['safe-alias'])->not->toHaveKey('not_found');

    @unlink($path);
});

it('logs a warning naming each skipped icon', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    [$finder, $path] = finderOverUnsafeSet();
    $logged = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
        $logged[] = $event;
    });

    $finder->find('test', ['unsafe', 'safe']);

    expect($logged)->toHaveCount(1)
        ->and($logged[0]->level)->toBe('warning')
        ->and($logged[0]->message)->toBe('Skipped an icon whose body failed IconBody::isSafe().')
        ->and($logged[0]->context)->toBe(['prefix' => 'test', 'icon' => 'unsafe']);

    @unlink($path);
});

/**
 * An IconFinder over a temporary set holding one icon with the given raw definition.
 *
 * Returns the finder and the temp file path, which the caller deletes.
 *
 * @param  array<string, mixed>  $icon
 * @return array{0: IconFinder, 1: string}
 */
function finderOverMalformedIcon(array $icon): array
{
    $path = sys_get_temp_dir().'/iconfinder-malformed-'.uniqid('', true).'.json';

    file_put_contents($path, json_encode([
        'prefix' => 'test',
        'lastModified' => 1,
        'icons' => ['broken' => $icon],
        'aliases' => [],
    ], JSON_THROW_ON_ERROR));

    $fileFinder = new class($path) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    return [new IconFinder($fileFinder), $path];
}

it('reports an icon with a non-string body as not found', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    [$finder, $path] = finderOverMalformedIcon(['body' => 5]);

    expect($finder->find('test', ['broken'])['broken'])->toBe([
        'icons' => [],
        'aliases' => [],
        'defaults' => [],
        'not_found' => ['broken'],
    ]);

    @unlink($path);
});

it('reports an icon with no body as not found', function () {
    config()->set('iconify-api.reject_unsafe_bodies', true);
    [$finder, $path] = finderOverMalformedIcon(['width' => 24]);

    expect($finder->find('test', ['broken'])['broken'])->toBe([
        'icons' => [],
        'aliases' => [],
        'defaults' => [],
        'not_found' => ['broken'],
    ]);

    @unlink($path);
});
