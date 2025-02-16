<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIconSetInfoSummary = array{
 *      prefix: string,
 *      lastModified: int,
 *      width?: int,
 *      height?: int,
 * }
 */
interface IconSetInfoSummaryFinder
{
    /**
     * @return TIconSetInfoSummary
     */
    public function find(string $prefix): array;
}
