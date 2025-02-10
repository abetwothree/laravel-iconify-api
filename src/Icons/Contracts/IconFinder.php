<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIconSetData = array{
 *      prefix: string,
 *      lastModified: int,
 *      icons: array<string, array{body: string}>,
 *      aliases: array<string, array<string,string>>,
 *      width?: int,
 *      height?: int,
 * }
 * @phpstan-type TIconData = array{
 *      icons: array<string, array{body: string}>,
 *      aliases: array<string, array<string,string>>,
 *      not_found?: array<int, string>,
 * }
 */
interface IconFinder
{
    /**
     * @param  array<int, string>  $icons
     * @return array<string, TIconData>
     */
    public function find(string $set, array $icons): array;
}
