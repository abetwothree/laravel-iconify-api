<?php

use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinder;

it('can find single file for individual icon set installed', function () {
    $finder = resolve(IconSetsFileFinder::class);
    $file = $finder->find('mdi');

    expect($file)
        ->toBeString()
        ->toBeFile()
        ->toContain('@iconify-json')
        ->not->toContain('@iconify/json/json');
});

it('can find icon set file for lucide in all sets, not individual', function () {
    $finder = resolve(IconSetsFileFinder::class);
    $file = $finder->find('lucide');

    expect($file)
        ->toBeString()
        ->toBeFile()
        ->toContain('@iconify/json/json')
        ->not->toContain('@iconify-json');
});

it('cannot find icon set file for non-existent icon set', function () {
    resolve(IconSetsFileFinder::class)->find('non-existent');
})->throws(Exception::class, 'Could not find the icons file for the set: non-existent');
