<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class IconifyDirective
{
    public function render(): string
    {
        $url = LaravelIconifyApi::domain().'/'.LaravelIconifyApi::path();

        // A config explicitly set to null means "no custom providers", but
        // config()->array() would throw on null instead of using the default.
        $configuredProviders = config('iconify-api.custom_providers');

        // config()->array() throws if the config isn't actually an array.
        $customProviders = $configuredProviders === null
            ? []
            : config()->array('iconify-api.custom_providers', []);

        // Merge before encoding so the output is one JSON object, not two
        // concatenated ones. A custom provider keyed '' overrides the default.
        $providers = array_merge(['' => ['resources' => [$url]]], $customProviders);

        // A literal `<` in a provider URL or key would break out of the script tag,
        // so escape it. Same flags as Laravel's Js::REQUIRED_FLAGS.
        $encodedProviders = json_encode($providers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

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
