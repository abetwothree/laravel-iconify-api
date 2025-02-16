<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache\Concerns;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use Illuminate\Support\Facades\Cache;

/**
 * @phpstan-import-type TIconData from IconFinderContract
 */
trait CachesIcons
{
    /**
     * @param  array<int,string>  $icons
     * @return array{found: array<string, TIconData>, not_found: array<int, string>}
     */
    public function getIcons(string $prefix, array $icons): array
    {
        $cacheResponse = [
            'found' => [],
            'not_found' => [],
        ];

        foreach ($icons as $icon) {
            /** @var TIconData|null $cachedIcon */
            $cachedIcon = Cache::store($this->store)->get($this->iconKey($prefix, $icon));

            if ($cachedIcon) {
                $cacheResponse['found'][$icon] = $cachedIcon;
            } else {
                $cacheResponse['not_found'][] = $icon;
            }
        }

        return $cacheResponse;
    }

    /**
     * @param  TIconData  $iconData
     */
    public function setIcon(string $prefix, string $icon, array $iconData): void
    {
        Cache::store($this->store)->put($this->iconKey($prefix, $icon), $iconData);
    }

    protected function iconKey(string $prefix, string $icon): string
    {
        return "{$this->cachePrefix}:{$prefix}:{$icon}";
    }
}
