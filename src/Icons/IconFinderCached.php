<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;

class IconFinderCached implements IconFinderContract
{
    public function __construct(
        protected IconFinder $iconFinder,
        protected CacheRepository $cacheRepository
    ) {}

    /** {@inheritDoc} */
    public function find(string $set, array $icons): array
    {
        $cachedIcons = $this->cacheRepository->getIcons($set, $icons);

        if (count($cachedIcons['not_found']) === 0) {
            return $cachedIcons['found'];
        }

        $foundIcons = $this->iconFinder->find($set, $cachedIcons['not_found']);

        foreach ($foundIcons as $icon => $iconData) {
            $this->cacheRepository->setIcon($set, $icon, $iconData);
        }

        return array_merge($cachedIcons['found'], $foundIcons);
    }
}
