<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use Illuminate\Support\Facades\Vite;

class IconifyDirective
{
    /**
     * Build the inline script that points Iconify's on-demand components at this app's API.
     *
     * The @iconify directive calls this on every render, so each request's CSP nonce
     * reaches the tag. An explicit nonce overrides the one Vite::cspNonce() holds.
     */
    public function render(?string $nonce = null): string
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

        $nonceAttribute = $this->nonceAttribute($nonce);

        return <<<HTML
            <script{$nonceAttribute} type="text/javascript">
                if(!window.IconifyProviders) {
                    window.IconifyProviders = {};
                }

                IconifyProviders = {$encodedProviders};
            </script>
        HTML;
    }

    /**
     * The script tag's nonce attribute with its leading space, or '' when there is no nonce.
     *
     * Falls back to the nonce @vite uses, so an app that calls Vite::useCspNonce() needs no
     * setup. Escaped because @iconify($nonce) accepts any Blade expression.
     */
    protected function nonceAttribute(?string $nonce): string
    {
        if ($nonce === null || $nonce === '') {
            $nonce = Vite::cspNonce();
        }

        // nonce="" can never match a CSP nonce-source, so print nothing instead.
        if ($nonce === null || $nonce === '') {
            return '';
        }

        return ' nonce="'.e($nonce).'"';
    }
}
