<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache;

use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CacheIconSetInfo;
use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CacheIconSetInfoSummary;
use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CachesIconFileSet;
use AbeTwoThree\LaravelIconifyApi\Cache\Concerns\CachesIcons;
use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class CacheRepository
{
    use CacheIconSetInfo;
    use CacheIconSetInfoSummary;
    use CachesIconFileSet;
    use CachesIcons;

    protected string $cachePrefix;

    protected string $store;

    public function __construct()
    {
        $this->store = LaravelIconifyApi::cacheStore();
        $this->cachePrefix = config()->string('iconify-api.cache_key_prefix');
    }
}
