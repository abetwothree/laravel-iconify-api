<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIconSetInfo = array{
 *      prefix: string,
 *      width: int,
 *      height: int,
 *      lastModified: int,
 * }
 */
interface IconSetInfoFinder
{
    /**
     * @return TIconSetInfo
     */
    public function find(string $set): array;
}
