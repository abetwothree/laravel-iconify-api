<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

if (! function_exists('iconify_svg')) {
    /**
     * @param  array<string, mixed>  $options
     */
    function iconify_svg(string $name, array $options = []): string
    {
        return LaravelIconifyApi::icon($name, $options);
    }
}
