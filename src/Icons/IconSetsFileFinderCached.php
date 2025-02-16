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

    public function find(string $prefix, string $type = 'icons'): string
    {
        $file = $this->cacheRepository->getFileSet($prefix, $type);

        if ($file !== null) {
            return $file;
        }

        $file = $this->iconSetsFileFinder->find($prefix, $type);
        $this->cacheRepository->setFileSet($prefix, $file, $type);

        return $file;
    }
}
