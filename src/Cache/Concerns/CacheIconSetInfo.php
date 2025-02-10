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
    public function getIconSetInfo(string $set): ?array
    {
        /** @var TIconSetInfo|null $data */
        $data = Cache::store($this->store)->get($this->iconSetInfoKey($set));

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param  TIconSetInfo  $iconSetInfo
     */
    public function setIconSetInfo(string $set, array $iconSetInfo): void
    {
        Cache::store($this->store)->put($this->iconSetInfoKey($set), $iconSetInfo);
    }

    protected function iconSetInfoKey(string $set): string
    {
        return "{$this->cachePrefix}:{$set}:info";
    }
}
