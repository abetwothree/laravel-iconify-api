<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

it('lets an icon() declared before the autoloader take precedence', function () {
    $result = Process::run([PHP_BINARY, '-d', 'display_errors=stderr', __DIR__.'/../Fixtures/LoadOrder/icon-declared-before-autoload.php']);

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and($result->output())->toBe('app-icon|app|namespaced');
});

it('skips a guarded icon() declared after the autoloader', function () {
    $result = Process::run([PHP_BINARY, '-d', 'display_errors=stderr', __DIR__.'/../Fixtures/LoadOrder/icon-declared-after-autoload.php']);

    expect($result->successful())->toBeTrue($result->errorOutput())
        ->and($result->output())->toBe('package');
});
