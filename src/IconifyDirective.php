<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class IconifyDirective
{
    public function render(): string
    {
        $url = LaravelIconifyApi::domain().'/'.LaravelIconifyApi::path();

        // A published config that explicitly sets this key to null must still mean
        // "no custom providers" (pre-strict-types, `empty(null)` was true): Arr::get(),
        // which backs config()->array(), returns that literal null for a key that exists
        // rather than falling back to the default, so it has to be normalised here first.
        $configuredProviders = config('iconify-api.custom_providers');

        // `config()->array()` throws on any other non-array where an inline `@var` on
        // `config()->get()` only promised one, and JSON_THROW_ON_ERROR turns an
        // unencodable config into an exception rather than a truncated script tag.
        $customProviders = $configuredProviders === null
            ? []
            : config()->array('iconify-api.custom_providers', []);

        // Built and encoded as one array so the emitted statement is a single JSON
        // object; encoding the default and the configured providers separately and
        // concatenating them produced two members back to back, which is not valid
        // object-literal syntax. array_merge() keeps a later duplicate string key over
        // an earlier one, preserving a custom provider explicitly keyed '' winning over
        // the default.
        $providers = array_merge(['' => ['resources' => [$url]]], $customProviders);

        $encodedProviders = json_encode($providers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return <<<HTML
            <script type="text/javascript">
                if(!window.IconifyProviders) {
                    window.IconifyProviders = {};
                }

                IconifyProviders = {$encodedProviders};
            </script>
        HTML;
    }
}
