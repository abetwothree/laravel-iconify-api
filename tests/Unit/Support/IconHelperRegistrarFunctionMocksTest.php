<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Support {

    function random_bytes(int $length): string
    {
        $mock = $GLOBALS['iconify_mock_random_bytes'] ?? null;

        if ($mock !== null) {
            return str_repeat($mock, max(1, intdiv($length, strlen($mock) ?: 1)));
        }

        return \random_bytes($length);
    }

    function fopen(string $filename, string $mode)
    {
        $mock = $GLOBALS['iconify_mock_fopen'] ?? null;

        if ($mock !== null) {
            return $mock;
        }

        return \fopen($filename, $mode);
    }

    function file_put_contents(string $filename, mixed $data, int $flags = 0): int|false
    {
        $mock = $GLOBALS['iconify_mock_file_put_contents'] ?? null;

        if ($mock !== null) {
            return $mock;
        }

        return \file_put_contents($filename, $data, $flags);
    }
}

namespace {

    use AbeTwoThree\LaravelIconifyApi\Support\IconHelperRegistrar;

    beforeEach(function () {
        unset($GLOBALS['iconify_mock_random_bytes'], $GLOBALS['iconify_mock_fopen'], $GLOBALS['iconify_mock_file_put_contents']);
    });

    afterEach(function () {
        unset($GLOBALS['iconify_mock_random_bytes'], $GLOBALS['iconify_mock_fopen'], $GLOBALS['iconify_mock_file_put_contents']);
    });

    if (! function_exists('already_registered_icon_helper')) {
        function already_registered_icon_helper(string $name, array $options = []): string
        {
            return 'already-registered';
        }
    }

    it('covers early function_exists return branch', function () {
        IconHelperRegistrar::register('already_registered_icon_helper');

        expect(already_registered_icon_helper('mdi:home'))->toBe('already-registered');
    });

    it('covers helper registrar early return when temp helper file cannot be created', function () {
        $GLOBALS['iconify_mock_fopen'] = false;

        IconHelperRegistrar::register('icon_helper_uncreatable');

        expect(function_exists('icon_helper_uncreatable'))->toBeFalse();
    });

    it('covers helper registrar branch when writing helper content fails', function () {
        $GLOBALS['iconify_mock_file_put_contents'] = false;

        IconHelperRegistrar::register('icon_helper_write_fail');

        expect(function_exists('icon_helper_write_fail'))->toBeFalse();
    });
}
