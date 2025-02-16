<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;

/**
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
class IconSetInfoFinderCached implements IconSetInfoFinderContract
{
    public function __construct(
        protected IconSetInfoFinder $infoFinder,
        protected CacheRepository $cacheRepository,
    ) {}

    /** {@inheritDoc} */
    public function find(string $prefix): array
    {
        $cachedInfo = $this->cacheRepository->getIconSetInfo($prefix);

        if ($cachedInfo !== null) {
            return $cachedInfo;
        }

        $info = $this->infoFinder->find($prefix);
        $this->cacheRepository->setIconSetInfo($prefix, $info);

        return $info;
    }
}
