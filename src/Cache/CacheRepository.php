<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache;

use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CachesIconFileSet;
use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CachesIcons;
use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CacheIconSetInfo;
use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class CacheRepository
{
    use CachesIconFileSet;
    use CachesIcons;
    use CacheIconSetInfo;

    protected string $cachePrefix;

    protected string $store;

    public function __construct()
    {
        $this->store = LaravelIconifyApi::cacheStore();
        $this->cachePrefix = config()->string('iconify-api.cache_key_prefix');
    }
}
