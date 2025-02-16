<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIcon = array{body:string}
 * @phpstan-type TIcons = array<string, TIcon>
 * @phpstan-type TAlias = array<string,string>
 * @phpstan-type TAliases = array<string, TAlias>
 * @phpstan-type TIconSetData = array{
 *      prefix: string,
 *      lastModified: int,
 *      icons: TIcons,
 *      aliases: TAliases,
 *      width?: int|null,
 *      height?: int|null,
 * }
 * @phpstan-type TIconData = array{
 *      icons: TIcons,
 *      aliases: TAliases,
 *      not_found?: array<int, string>,
 * }
 */
interface IconFinder
{
    /**
     * @param  array<int, string>  $icons
     * @return array<string, TIconData>
     */
    public function find(string $prefix, array $icons): array;
}
