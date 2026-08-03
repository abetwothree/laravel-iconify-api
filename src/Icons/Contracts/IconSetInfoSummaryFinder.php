<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * The dimensions are copied verbatim out of the icon set's JSON root, which this
 * package does not validate or coerce, so they are declared as wide as what a file can
 * actually hold. See the note on `TIconSetData` in the IconFinder contract.
 *
 * @phpstan-type TIconSetInfoSummary = array{
 *      prefix: string,
 *      lastModified: int,
 *      left?: int|float|string,
 *      top?: int|float|string,
 *      width?: int|float|string,
 *      height?: int|float|string,
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
