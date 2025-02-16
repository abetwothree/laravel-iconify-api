<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache\Concerns;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoSummaryFinder as IconSetInfoSummaryFinderContract;
use Illuminate\Support\Facades\Cache;

/**
 * @phpstan-import-type TIconSetInfoSummary from IconSetInfoSummaryFinderContract
 */
trait CacheIconSetInfoSummary
{
    /**
     * @return TIconSetInfoSummary|null
     */
    public function getIconSetInfoSummary(string $prefix): ?array
    {
        /** @var TIconSetInfoSummary|null $data */
        $data = Cache::store($this->store)->get($this->iconSetInfoSummaryKey($prefix));

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    /**
     * @param  TIconSetInfoSummary  $iconSetSummary
     */
    public function setIconSetInfoSummary(string $prefix, array $iconSetSummary): void
    {
        Cache::store($this->store)->put($this->iconSetInfoSummaryKey($prefix), $iconSetSummary);
    }

    protected function iconSetInfoSummaryKey(string $prefix): string
    {
        return "{$this->cachePrefix}:{$prefix}:info:summary";
    }
}
