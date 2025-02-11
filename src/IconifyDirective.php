<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class IconifyDirective
{
    public function render(): string
    {
        $url = LaravelIconifyApi::domain().'/'.LaravelIconifyApi::path();

        /** @var array<string, array{resources: array<int, string>, rotate?: int}> $customProviders */
        $customProviders = config()->get('iconify-api.custom_providers', []);

        if (empty($customProviders)) {
            $customProviders = '';
        } else {
            $customProviders = json_encode($customProviders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

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
                    {$customProviders}
                };
            </script>
        HTML;
    }
}
