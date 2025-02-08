<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIconSetData = array{
 *      prefix: string,
 *      width: int,
 *      height: int,
 *      lastModified: int,
 *      icons: array<string, array{body: string}>,
 *      aliases: array<string, array<string,string>>,
 * }
 * @phpstan-type TIconResponse = array{
 *      prefix: string,
 *      lastModified: int,
 *      width: int,
 *      height: int,
 *      aliases: array<string, array<string,string>>,
 *      icons: array<string, array<string, string>>,
 *      not_found?: array<int, string>,
 * }
 */
interface IconFinder
{
    /**
     * @param  array<int, string>  $icons
     * @return array<string, TIconResponse>
     */
    public function find(string $set, array $icons): array;
}
