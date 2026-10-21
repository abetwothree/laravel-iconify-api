<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string icon(string $name, array<string, mixed> $options = [])
 *
 * @see \AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi
 */
class LaravelIconifyApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi::class;
    }
}
