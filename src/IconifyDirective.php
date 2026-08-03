<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class IconifyDirective
{
    public function render(): string
    {
        $url = LaravelIconifyApi::domain().'/'.LaravelIconifyApi::path();

        // `config()->array()` throws on a non-array where an inline `@var` on
        // `config()->get()` only promised one, and JSON_THROW_ON_ERROR turns an
        // unencodable config into an exception rather than a truncated script tag.
        $customProviders = config()->array('iconify-api.custom_providers', []);

        $encodedProviders = $customProviders === []
            ? ''
            : json_encode($customProviders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return <<<HTML
            <script type="text/javascript">
                if(!window.IconifyProviders) {
                    window.IconifyProviders = {};
                }

                IconifyProviders = {
                    '': {
                        resources: [
                            '{$url}',
                        ],
                    },
                    {$encodedProviders}
                };
            </script>
        HTML;
    }
}
