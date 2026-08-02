<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;

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
