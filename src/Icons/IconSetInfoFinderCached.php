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
        protected CacheRepository $cacheRepository,
        protected IconSetInfoFinder $iconSetInfoFinder
    ) {}

    /** {@inheritDoc} */
    public function find(string $set): array
    {
        /** @var TIconSetInfo|null $iconSetInfo */
        $iconSetInfo = $this->cacheRepository->getIconSetInfo($set);

        if ($iconSetInfo === null) {
            $iconSetInfo = $this->iconSetInfoFinder->find($set);

            $this->cacheRepository->setIconSetInfo($set, $iconSetInfo);
        }

        return $iconSetInfo;
    }
}
