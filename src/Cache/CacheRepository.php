<?php

declare(strict_types=1);

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

    /**
     * Seconds a cached miss lives for. Zero disables negative caching.
     */
    protected int $notFoundTtl;

    /**
     * Whether IconFinder drops icons with unsafe bodies, which moves icon keys to their own space.
     */
    protected bool $rejectUnsafeBodies;

    public function __construct()
    {
        $this->store = LaravelIconifyApi::cacheStore();
        $this->cachePrefix = config()->string('iconify-api.cache_key_prefix');
        $this->notFoundTtl = max(0, config()->integer('iconify-api.not_found_cache_ttl', 300));
        $this->rejectUnsafeBodies = config()->boolean('iconify-api.reject_unsafe_bodies', false);
    }
}
