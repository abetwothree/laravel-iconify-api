<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache\Concerns;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use Illuminate\Support\Facades\Cache;

/**
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
trait CacheIconSetInfo
{
    /**
     * @return TIconSetInfo|null
     */
    public function getIconSetInfo(string $prefix): ?array
    {
        /** @var TIconSetInfo|null $data */
        $data = Cache::store($this->store)->get($this->iconSetInfoKey($prefix));

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param  TIconSetInfo  $iconSetInfo
     */
    public function setIconSetInfo(string $prefix, $iconSetInfo): void
    {
        Cache::store($this->store)->put($this->iconSetInfoKey($prefix), $iconSetInfo);
    }

    protected function iconSetInfoKey(string $prefix): string
    {
        return "{$this->cachePrefix}:{$prefix}:info";
    }
}
