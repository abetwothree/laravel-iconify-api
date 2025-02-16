<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIconSetInfo = array{
 *      name: string,
 *      total: int,
 *      version: string,
 *      author: array{
 *          name: string,
 *          url: string,
 *      },
 *      license: array{
 *          title: string,
 *          spdx: string,
 *          url: string,
 *      },
 *      height: int,
 *      category: string,
 *      tags: array<int,string>,
 *      palette: bool,
 *      samples?: array<int,string>,
 * }
 */
interface IconSetInfoFinder
{
    /**
     * @return TIconSetInfo
     */
    public function find(string $prefix): array;
}
