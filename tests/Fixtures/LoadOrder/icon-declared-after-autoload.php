<?php

declare(strict_types=1);

require __DIR__.'/../../../vendor/autoload.php';

if (! function_exists('icon')) {
    function icon(string $name, array $options = []): string
    {
        return 'app-icon';
    }
}

echo realpath((string) (new ReflectionFunction('icon'))->getFileName()) === realpath(__DIR__.'/../../../src/helpers.php') ? 'package' : 'app';
