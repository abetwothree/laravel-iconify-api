<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

if (! function_exists('AbeTwoThree\LaravelIconifyApi\icon')) {
    /**
     * Render an Iconify icon as inline SVG.
     *
     * @param  array<string, mixed>  $options
     */
    function icon(string $name, array $options = []): string
    {
        return LaravelIconifyApi::icon($name, $options);
    }
}
