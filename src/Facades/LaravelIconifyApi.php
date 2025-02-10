<?php

namespace AbeTwoThree\LaravelIconifyApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi
 */
class LaravelIconifyApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi::class;
    }
}
