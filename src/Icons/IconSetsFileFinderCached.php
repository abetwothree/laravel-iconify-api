<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Cache\CacheRepository;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;

class IconSetsFileFinderCached implements IconSetsFileFinderContract
{
    public function __construct(
        protected IconSetsFileFinder $iconSetsFileFinder,
        protected CacheRepository $cacheRepository
    ) {}

    public function find(string $set): string
    {
        $file = $this->cacheRepository->getFileSet($set);

        if ($file !== null) {
            return $file;
        }

        $file = $this->iconSetsFileFinder->find($set);

        $this->cacheRepository->setFileSet($set, $file);

        return $file;
    }
}
