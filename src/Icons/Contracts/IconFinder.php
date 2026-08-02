<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * @phpstan-type TIcon = array{
 *     body:string,
 *     left?:int,
 *     top?:int,
 *     width?:int,
 *     height?:int,
 *     rotate?:int|string,
 *     hFlip?:bool,
 *     vFlip?:bool,
 *     hidden?:bool,
 * }
 * @phpstan-type TIcons = array<string, TIcon>
 * @phpstan-type TAlias = array{
 *     parent: string,
 *     rotate?:int|string,
 *     hFlip?:bool,
 *     vFlip?:bool,
 *     left?:int,
 *     top?:int,
 *     width?:int,
 *     height?:int,
 *     hidden?:bool,
 * }
 * @phpstan-type TAliases = array<string, TAlias>
 * @phpstan-type TNotFound = array<int, string>
 * @phpstan-type TIconSetData = array{
 *      prefix: string,
 *      lastModified: int,
 *      icons: TIcons,
 *      aliases: TAliases,
 *      width?: int|null,
 *      height?: int|null,
 * }
 * @phpstan-type TIconDefaults = array{
 *      left?:int|float,
 *      top?:int|float,
 *      width?:int|float,
 *      height?:int|float,
 *      rotate?:int,
 *      hFlip?:bool,
 *      vFlip?:bool,
 * }
 * @phpstan-type TIconData = array{
 *      icons: TIcons,
 *      aliases: TAliases,
 *      defaults: TIconDefaults,
 *      not_found?: TNotFound,
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
