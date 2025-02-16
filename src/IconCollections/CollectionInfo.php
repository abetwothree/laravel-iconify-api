<?php

namespace AbeTwoThree\LaravelIconifyApi\IconCollections;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use Exception;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;

/**
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
class CollectionInfo
{
    public function __construct(
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /**
     * @return TIconSetInfo
     */
    public function get(string $prefix): array
    {
        return $this->iconSetInfoFinder->find($prefix);
    }
}
