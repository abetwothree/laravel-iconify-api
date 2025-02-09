<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class IconifyDirective
{
    public function render(): string
    {
        $url = LaravelIconifyApi::domain().'/'.LaravelIconifyApi::path();
        $customProviders = $this->gatherCustomProviders();

        if (empty($customProviders)) {
            $customProviders = '';
        } else {
            $customProviders = json_encode($customProviders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return <<<HTML
            <script>
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

    /**
     * @return array<string, array{resources: array<int, string>, rotate?: int}>
     */
    protected function gatherCustomProviders(): array
    {
        /**
         * @var array<string, array{resources: array<int, string>, rotate?: int}> $customProviders
         */
        $customProviders = config()->get('iconify-api.custom_providers', []);
        $providerList = [];

        foreach ($customProviders as $provider => $data) {
            $providerList[$provider] = $data;
        }

        return $providerList;
    }
}
