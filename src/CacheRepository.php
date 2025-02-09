<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use Illuminate\Support\Facades\Cache;
use Exception;
use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

/**
 * @phpstan-import-type TIconResponse from IconFinderContract
 */
class CacheRepository
{
    protected string $cachePrefix;

    protected string $store;

    public function __construct()
    {
        $this->store = LaravelIconifyApi::cacheStore();
        $this->cachePrefix = config()->string('iconify-api.cache_key_prefix');
    }

    /**
     * @param  array<int,string>  $icons
     * @return array{found: array<string, TIconResponse>, not_found: array<int, string>}
     */
    public function getIcons(string $set, array $icons): array
    {
        $cacheResponse = [
            'found' => [],
            'not_found' => [],
        ];

        foreach ($icons as $icon) {
            /** @var TIconResponse|null $cachedIcon */
            $cachedIcon = Cache::store($this->store)->get($this->iconKey($set, $icon));

            if ($cachedIcon) {
                $cacheResponse['found'][$icon] = $cachedIcon;
            } else {
                $cacheResponse['not_found'][] = $icon;
            }
        }

        return $cacheResponse;
    }

    /**
     * @param  TIconResponse  $iconData
     */
    public function setIcon(string $set, string $icon, array $iconData): void
    {
        Cache::store($this->store)->put($this->iconKey($set, $icon), $iconData);
    }

    protected function iconKey(string $set, string $icon): string
    {
        return "{$this->cachePrefix}:{$set}:{$icon}";
    }

    public function getFileSet(string $set): ?string
    {
        $file = Cache::store($this->store)->get($this->fileSetKey($set));

        if (! is_string($file)) {
            return null;
        }

        return $file;
    }

    public function setFileSet(string $set, string $file): void
    {
        Cache::store($this->store)->put($this->fileSetKey($set), $file);
    }

    protected function fileSetKey(string $set): string
    {
        return "{$this->cachePrefix}:{$set}:file";
    }
}
