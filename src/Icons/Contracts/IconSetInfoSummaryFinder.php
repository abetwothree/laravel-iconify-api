<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIconSetInfoSummary = array{
 *      prefix: string,
 *      lastModified: int,
 *      left?: int,
 *      top?: int,
 *      width?: int,
 *      height?: int,
 *      provider?: string,
 * }
 */
interface IconSetInfoSummaryFinder
{
    /**
     * @return TIconSetInfoSummary
     */
    public function find(string $prefix): array;
}
