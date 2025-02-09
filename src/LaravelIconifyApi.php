<?php

namespace AbeTwoThree\LaravelIconifyApi;

use Exception;
use Illuminate\Support\Str;

class LaravelIconifyApi
{
    public function iconsLocation(): string
    {
        return config()->string('iconify-api.icons_location');
    }

    public function fullSetLocation(): string
    {
        return $this->iconsLocation().'/@iconify';
    }

    public function singleSetLocation(): string
    {
        return $this->iconsLocation().'/@iconify-json';
    }

    public function cacheStore(): string
    {
        $store = config()->get('iconify-api.cache_store') ?? config()->get('cache.default');

        if (! is_string($store)) {
            throw new Exception('Cache store must be a string');
        }

        return $store;
    }

    public function domain(): string
    {
        $domain = config()->get('iconify-api.route_domain') ?? config()->get('app.url');

        if (! is_string($domain) || empty($domain)) {
            throw new Exception('Domain must be a set in your env or config files');
        }

        return $domain;
    }

    public function path(): string
    {
        return Str::finish(config()->string('iconify-api.route_path'), '/').'api';
    }
}
