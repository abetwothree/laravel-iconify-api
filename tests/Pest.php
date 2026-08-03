<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

uses(TestCase::class)->in(__DIR__);

/**
 * Every cache key currently held by a store, in insertion order.
 *
 * Reads the backing property rather than calling `ArrayStore::all()`, which only exists
 * in recent Laravel — this package supports 11 through 13, and the lowest-dependency CI
 * job resolves a framework without it.
 *
 * @return array<int, string>
 */
function cachedKeys(string $store = 'array'): array
{
    $storage = new ReflectionProperty(Cache::store($store)->getStore(), 'storage');
    $storage->setAccessible(true);

    /** @var array<string, mixed> $entries */
    $entries = $storage->getValue(Cache::store($store)->getStore());

    return array_keys($entries);
}

/**
 * Decode the `IconifyProviders = {...};` object literal out of a rendered `@iconify` tag.
 *
 * Matching fragments of the JS with `toContain()` can pass against a right-hand side that
 * is not valid JSON at all — that is how a duplicated-object bug went undetected. Decoding
 * it is the only assertion that actually proves the emitted statement is one JSON object.
 * The anchored, non-`window.`-prefixed match skips the `if(!window.IconifyProviders)` guard,
 * which contains the same substring earlier in the string.
 *
 * @return array<string, mixed>
 */
function decodeIconifyProviders(string $rendered): array
{
    preg_match('/^\s*IconifyProviders = (\{.*\});$/ms', $rendered, $matches);

    if (! array_key_exists(1, $matches)) {
        throw new RuntimeException('No IconifyProviders assignment found in rendered output.');
    }

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

    return $decoded;
}
