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
     * Bytes of an icon name that may be written into a cache key verbatim.
     *
     * Bounds the name segment only. The icon set prefix ahead of it is caller-supplied
     * and unbounded here, an unenforced precondition shared with every key builder in
     * this package. The longest name in the bundled sets is 99 bytes, so no published
     * set comes near this.
     */
    protected const MAX_ICON_KEY_NAME_BYTES = 128;

    /**
     * An icon name that may be written into a cache key verbatim.
     *
     * Printable ASCII, minus the space, the `:` that separates key segments, and `/`.
     * Control characters and high bytes are out because memcached rejects them.
     */
    protected const ICON_KEY_NAME_PATTERN = '~^[^\x00-\x20\x7f-\xff:/]++$~D';

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

            // The shape version lives in the key, so anything found here is current.
            // `defaults` is optional by contract and must not be read as staleness.
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
     * A hit is immutable for as long as the *installed version* of the icon set is, so
     * it is stored without a TTL. Nothing keys off the set file's mtime or version, so
     * upgrading an icon package leaves the old bodies cached until `php artisan
     * cache:clear`.
     *
     * A miss expires: `IconFinder::find()` returns an entry for every requested name,
     * so a request can mint one per name it invents and nothing here calls `forget()`.
     * A TTL of zero disables negative caching altogether.
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
     * A flat `{prefix}:{set}:{name}` scheme let a name such as `info` — shipped by 70
     * of the 235 sets in `@iconify/json` — address an icon set metadata key exactly and
     * overwrite it permanently. Every key builder here therefore commits a literal
     * segment before any caller-supplied value.
     *
     * The `2` is the entry shape version, bumped whenever the cached array shape
     * changes so stale entries retire without a marker key inside them.
     */
    protected function iconKey(string $prefix, string $icon): string
    {
        return "{$this->cachePrefix}:{$prefix}:icon:2:".$this->iconKeySegment($icon);
    }

    /**
     * The icon name as written, or a hash of it when a cache key cannot hold it.
     *
     * Names are not validated on the way in: a hand-authored set reached through a
     * custom `icons_location` may use one that Iconify's own `matchIconName` would
     * reject, and a name that does not exist is simply a miss. Cache stores are less
     * forgiving — memcached refuses a key holding a space or a control character and
     * caps it at 250 bytes, and the `database` store keeps keys in a `varchar(255)`.
     *
     * The hash covers the whole name, never a prefix, so two names cannot land on one
     * key, and the branches cannot meet: the verbatim branch rules `:` out and the
     * hashed branch always writes one at offset 1. SHA-256 rather than a fast
     * non-cryptographic hash because the input is caller-controlled.
     *
     * Every name in every published icon set takes the verbatim branch, so the
     * documented key format still holds and no existing entry is orphaned.
     */
    protected function iconKeySegment(string $icon): string
    {
        if (strlen($icon) <= self::MAX_ICON_KEY_NAME_BYTES && preg_match(self::ICON_KEY_NAME_PATTERN, $icon) === 1) {
            return $icon;
        }

        return 'h:'.hash('sha256', $icon);
    }
}
