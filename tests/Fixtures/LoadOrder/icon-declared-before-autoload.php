<?php

declare(strict_types=1);

function icon(string $name, array $options = []): string
{
    return 'app-icon';
}

require __DIR__.'/../../../vendor/autoload.php';

echo implode('|', [
    icon('heroicons:clock'),
    realpath((string) (new ReflectionFunction('icon'))->getFileName()) === realpath(__FILE__) ? 'app' : 'package',
    function_exists('AbeTwoThree\LaravelIconifyApi\icon') ? 'namespaced' : 'no-namespaced',
]);
