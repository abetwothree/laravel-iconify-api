<?php

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinderCached;

it('covers icon finder cached hit and miss branches', function () {
    $repo = Mockery::mock(CacheRepository::class);
    $finder = Mockery::mock(IconFinder::class);

    $repo->shouldReceive('getIcons')->with('mdi', ['home'])->once()->andReturn([
        'found' => ['home' => ['icons' => ['home' => ['body' => '<path/>']], 'aliases' => []]],
        'not_found' => [],
    ]);

    $finder->shouldNotReceive('find');

    $cached = new IconFinderCached($finder, $repo);
    expect($cached->find('mdi', ['home']))->toHaveKey('home');

    $repo2 = Mockery::mock(CacheRepository::class);
    $finder2 = Mockery::mock(IconFinder::class);

    $repo2->shouldReceive('getIcons')->with('mdi', ['home', 'account'])->once()->andReturn([
        'found' => [],
        'not_found' => ['home', 'account'],
    ]);

    $finder2->shouldReceive('find')->once()->with('mdi', ['home', 'account'])->andReturn([
        'home' => ['icons' => ['home' => ['body' => '<path/>']], 'aliases' => []],
        'account' => ['icons' => ['account' => ['body' => '<path/>']], 'aliases' => []],
    ]);

    $repo2->shouldReceive('setIcon')->twice();

    $cached2 = new IconFinderCached($finder2, $repo2);
    expect($cached2->find('mdi', ['home', 'account']))->toHaveKeys(['home', 'account']);
});

it('covers icon set info finder cached hit and miss branches', function () {
    $repo = Mockery::mock(CacheRepository::class);
    $finder = Mockery::mock(IconSetInfoFinder::class);

    $repo->shouldReceive('getIconSetInfo')->with('mdi')->once()->andReturn(['prefix' => 'mdi']);
    $finder->shouldNotReceive('find');

    $cached = new IconSetInfoFinderCached($finder, $repo);
    expect($cached->find('mdi'))->toBe(['prefix' => 'mdi']);

    $repo2 = Mockery::mock(CacheRepository::class);
    $finder2 = Mockery::mock(IconSetInfoFinder::class);

    $repo2->shouldReceive('getIconSetInfo')->with('mdi')->once()->andReturn(null);
    $finder2->shouldReceive('find')->with('mdi')->once()->andReturn(['prefix' => 'mdi']);
    $repo2->shouldReceive('setIconSetInfo')->with('mdi', ['prefix' => 'mdi'])->once();

    $cached2 = new IconSetInfoFinderCached($finder2, $repo2);
    expect($cached2->find('mdi'))->toBe(['prefix' => 'mdi']);
});

it('covers icon set info summary finder cached hit and miss branches', function () {
    $repo = Mockery::mock(CacheRepository::class);
    $finder = Mockery::mock(IconSetInfoSummaryFinder::class);

    $repo->shouldReceive('getIconSetInfoSummary')->with('mdi')->once()->andReturn(['prefix' => 'mdi']);
    $finder->shouldNotReceive('find');

    $cached = new IconSetInfoSummaryFinderCached($repo, $finder);
    expect($cached->find('mdi'))->toBe(['prefix' => 'mdi']);

    $repo2 = Mockery::mock(CacheRepository::class);
    $finder2 = Mockery::mock(IconSetInfoSummaryFinder::class);

    $repo2->shouldReceive('getIconSetInfoSummary')->with('mdi')->once()->andReturn(null);
    $finder2->shouldReceive('find')->with('mdi')->once()->andReturn(['prefix' => 'mdi']);
    $repo2->shouldReceive('setIconSetInfoSummary')->with('mdi', ['prefix' => 'mdi'])->once();

    $cached2 = new IconSetInfoSummaryFinderCached($repo2, $finder2);
    expect($cached2->find('mdi'))->toBe(['prefix' => 'mdi']);
});

it('covers icon set file finder cached hit and miss branches', function () {
    $repo = Mockery::mock(CacheRepository::class);
    $finder = Mockery::mock(IconSetsFileFinder::class);

    $repo->shouldReceive('getFileSet')->with('mdi', 'icons')->once()->andReturn('/tmp/icons.json');
    $finder->shouldNotReceive('find');

    $cached = new IconSetsFileFinderCached($finder, $repo);
    expect($cached->find('mdi'))->toBe('/tmp/icons.json');

    $repo2 = Mockery::mock(CacheRepository::class);
    $finder2 = Mockery::mock(IconSetsFileFinder::class);

    $repo2->shouldReceive('getFileSet')->with('mdi', 'info')->once()->andReturn(null);
    $finder2->shouldReceive('find')->with('mdi', 'info')->once()->andReturn('/tmp/info.json');
    $repo2->shouldReceive('setFileSet')->with('mdi', '/tmp/info.json', 'info')->once();

    $cached2 = new IconSetsFileFinderCached($finder2, $repo2);
    expect($cached2->find('mdi', 'info'))->toBe('/tmp/info.json');
});
