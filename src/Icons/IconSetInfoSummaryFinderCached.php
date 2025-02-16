<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoSummaryFinder as IconSetInfoSummaryFinderContract;

/**
 * @phpstan-import-type TIconSetInfoSummary from IconSetInfoSummaryFinderContract
 */
class IconSetInfoSummaryFinderCached implements IconSetInfoSummaryFinderContract
{
    public function __construct(
        protected CacheRepository $cacheRepository,
        protected IconSetInfoSummaryFinder $iconSetInfoSummaryFinder
    ) {}

    /** {@inheritDoc} */
    public function find(string $prefix): array
    {
        /** @var TIconSetInfoSummary|null $iconSetInfo */
        $iconSetInfo = $this->cacheRepository->getIconSetInfoSummary($prefix);

        if ($iconSetInfo === null) {
            $iconSetInfo = $this->iconSetInfoSummaryFinder->find($prefix);

            $this->cacheRepository->setIconSetInfoSummary($prefix, $iconSetInfo);
        }

        return $iconSetInfo;
    }
}
