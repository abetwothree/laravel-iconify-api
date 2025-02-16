<?php

namespace AbeTwoThree\LaravelIconifyApi\IconCollections;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;

/**
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 *
 * @phpstan-type TIconInfoCollection = array<string, TIconSetInfo>
 */
class CollectionsInfo
{
    public function __construct(
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /**
     * @return TIconInfoCollection
     */
    public function get(): array
    {
        $data = [];
        foreach (LaravelIconifyApiFacade::prefixes() as $prefix) {
            $data[$prefix] = $this->iconSetInfoFinder->find($prefix);
        }

        return $data;
    }
}
