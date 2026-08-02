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
     * The longest of the 344,625 names in the 235 sets bundled with `@iconify/json` is
     * 99 bytes, so no published set comes near this. It keeps the part of the key this
     * package controls — prefix, icon set prefix, literal segments and name — inside
     * memcached's 250-byte key limit and the `database` store's `varchar(255)` column
     * with room left for the application's own cache prefix.
     */
    protected const MAX_ICON_KEY_NAME_BYTES = 128;

    /**
     * An icon name that may be written into a cache key verbatim.
     *
     * Everything printable in ASCII except the space, the `:` that separates key
     * segments, and the `/` — a key is an opaque string to Laravel, but not every store
     * treats it as one, which is why the `file` store hashes it before building a path.
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
     *
     * The name itself is not filtered before it gets here, so the last segment is
     * conditionally hashed — see `iconKeySegment()`.
     */
    protected function iconKey(string $prefix, string $icon): string
    {
        return "{$this->cachePrefix}:{$prefix}:icon:2:".$this->iconKeySegment($icon);
    }

    /**
     * The icon name as written, or a hash of it when a cache key cannot hold it.
     *
     * Names are not validated on the way in: a hand-authored set reached through a
     * custom `icons_location` may legitimately use one that Iconify's own `matchIconName`
     * would reject, and a name that does not exist is simply a miss. Cache stores are
     * less forgiving than the finder — memcached refuses a key holding a space or a
     * control character and caps it at 250 bytes, and the `database` store keeps keys in
     * a `varchar(255)` — so a name a key cannot hold is replaced rather than passed on.
     *
     * The replacement hashes the whole name, never a prefix of it, so two names cannot
     * land on one key. The two branches cannot meet either: the verbatim branch rules
     * `:` out, and the hashed branch always writes one at offset 1. `getIcons()` and
     * `setIcon()` both build their key through here, so a hashed name reads back from
     * the key it was written to. SHA-256 rather than a fast non-cryptographic hash
     * because the input is caller-controlled and a collision would serve one name's
     * entry for another's.
     *
     * Every name in every published icon set takes the verbatim branch, so the key
     * format stays as documented and no existing entry is orphaned.
     */
    protected function iconKeySegment(string $icon): string
    {
        if (strlen($icon) <= self::MAX_ICON_KEY_NAME_BYTES && preg_match(self::ICON_KEY_NAME_PATTERN, $icon) === 1) {
            return $icon;
        }

        return 'h:'.hash('sha256', $icon);
    }
}
