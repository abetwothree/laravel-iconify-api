<?php

namespace AbeTwoThree\LaravelIconifyApi;

use Exception;

class LaravelIconifyApi
{
    public function cacheStore(): string
    {
        $store = config()->get('iconify-api.cache_store') ?? config()->get('cache.default');

        if (! is_string($store)) {
            throw new Exception('Cache store must be a string');
        }

        return $store;
    }
}
