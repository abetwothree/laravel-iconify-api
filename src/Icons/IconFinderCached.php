<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;

class IconFinderCached implements IconFinderContract
{
    public function __construct(
        protected IconFinder $iconFinder,
        protected CacheRepository $cacheRepository
    ) {}

    /** {@inheritDoc} */
    public function find(string $prefix, array $icons): array
    {
        $cachedIcons = $this->cacheRepository->getIcons($prefix, $icons);

        if (count($cachedIcons['not_found']) === 0) {
            return $cachedIcons['found'];
        }

        $foundIcons = $this->iconFinder->find($prefix, $cachedIcons['not_found']);

        foreach ($foundIcons as $icon => $iconData) {
            $this->cacheRepository->setIcon($prefix, $icon, $iconData);
        }

        return array_merge($cachedIcons['found'], $foundIcons);
    }
}
