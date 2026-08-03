<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinder;

it('covers reading info from individual icon set files and injects missing prefix', function () {
    $base = sys_get_temp_dir().'/iconify-info-single-'.uniqid('', true);
    $dir = $base.'/@iconify-json/mdi';
    mkdir($dir, 0777, true);
    $file = $dir.'/info.json';

    file_put_contents($file, json_encode([
        'name' => 'Test Set',
        'total' => 12,
    ], JSON_THROW_ON_ERROR));

    $finder = Mockery::mock(IconSetsFileFinder::class);
    $finder->shouldReceive('find')->with('mdi', 'info')->once()->andReturn($file);

    config()->set('iconify-api.icons_location', $base);

    $infoFinder = new IconSetInfoFinder($finder);
    $info = $infoFinder->find('mdi');

    expect($info['prefix'])->toBe('mdi');
    expect($info['name'])->toBe('Test Set');
});

it('covers reading info section from full json set files', function () {
    $dir = sys_get_temp_dir().'/iconify-info-full-'.uniqid('', true);
    mkdir($dir, 0777, true);
    $file = $dir.'/set.json';

    file_put_contents($file, json_encode([
        'prefix' => 'heroicons',
        'info' => [
            'name' => 'Heroicons',
            'total' => 100,
        ],
    ], JSON_THROW_ON_ERROR));

    $finder = Mockery::mock(IconSetsFileFinder::class);
    $finder->shouldReceive('find')->with('heroicons', 'info')->once()->andReturn($file);

    config()->set('iconify-api.icons_location', sys_get_temp_dir().'/different-location');

    $infoFinder = new IconSetInfoFinder($finder);
    $info = $infoFinder->find('heroicons');

    expect($info['name'])->toBe('Heroicons');
});
