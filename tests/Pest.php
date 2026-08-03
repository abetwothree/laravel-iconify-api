<?php

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
