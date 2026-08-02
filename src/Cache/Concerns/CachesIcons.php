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
            $cachedIcon = Cache::store($this->store)->get($this->iconKey($prefix, $icon));

            // The shape version lives in the key, so anything found here is already
            // known to be current. `defaults` is optional by contract, so its absence
            // must not be read as staleness — see IconFinderContract.
            if (is_array($cachedIcon) && isset($cachedIcon['icons'])) {
                /** @var TIconData $cachedIcon */
                $cacheResponse['found'][$icon] = $cachedIcon;
            } else {
                $cacheResponse['not_found'][] = $icon;
            }
        }

        return $cacheResponse;
    }

    /**
     * Cache one icon entry.
     *
     * A hit is immutable for as long as the icon set stays installed, so it is stored
     * without a TTL. A miss is not: `IconFinder::find()` returns an entry for every
     * requested name, so one request can mint an entry per distinct name it asks for,
     * and nothing in this package ever calls `forget()`. Negative entries therefore
     * expire, which bounds what a request can leave behind. A TTL of zero disables
     * negative caching altogether.
     *
     * @param  TIconData  $iconData
     */
    public function setIcon(string $prefix, string $icon, array $iconData): void
    {
        $key = $this->iconKey($prefix, $icon);

        if (($iconData['not_found'] ?? []) !== []) {
            if ($this->notFoundTtl > 0) {
                Cache::store($this->store)->put($key, $iconData, $this->notFoundTtl);
            }

            return;
        }

        Cache::store($this->store)->put($key, $iconData);
    }

    /**
     * Icon entries live under their own `icon:` segment.
     *
     * The icon name is attacker- and author-controlled and is the last segment, so a
     * flat `{prefix}:{set}:{name}` scheme let a name such as `info` — shipped by 70 of
     * the 235 sets in `@iconify/json` — address an icon set metadata key exactly and
     * overwrite it permanently. Every key builder in this package therefore commits to
     * a literal segment before any caller-supplied value.
     *
     * The `2` is the entry shape version: it is bumped whenever the cached array shape
     * changes, which retires stale entries without needing a marker key inside them.
     */
    protected function iconKey(string $prefix, string $icon): string
    {
        return "{$this->cachePrefix}:{$prefix}:icon:2:{$icon}";
    }
}
