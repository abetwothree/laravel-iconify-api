<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\IconSvgRenderer;

if (! function_exists('icon')) {
    /**
     * Render an Iconify icon as inline SVG.
     *
     * @param  array<string, mixed>  $options
     */
    function icon(string $name, array $options = []): string
    {
        return app(IconSvgRenderer::class)->render($name, $options);
    }
}
