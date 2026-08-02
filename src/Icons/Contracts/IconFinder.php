<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

/**
 * Shapes for the icon set data this package reads and the responses it returns.
 *
 * The icon set root carries a value for every property in `defaultIconProps` — see
 * packages/utils/src/icon-set/validate-basic.ts:12-17 — and `IconFinder` reads all of
 * them, `rotate`/`hFlip`/`vFlip` included. The scalar unions on `TIconSetData` and
 * `TIconDefaults` are deliberately wide: an icon set is a JSON file this package does
 * not validate, upstream's own `quicklyValidateIconSet()` is never run on it, and
 * nothing between `json_decode()` and the renderer coerces the values. A root written
 * `{"width": "24", "rotate": "1", "hFlip": 1}` is exactly what a consumer is handed
 * back, so the declaration says so rather than promising a narrower type nothing here
 * enforces.
 *
 * @phpstan-type TIcon = array{
 *     body:string,
 *     left?:int,
 *     top?:int,
 *     width?:int,
 *     height?:int,
 *     rotate?:int|float|string,
 *     hFlip?:bool,
 *     vFlip?:bool,
 *     hidden?:bool,
 * }
 * @phpstan-type TIcons = array<string, TIcon>
 * @phpstan-type TAlias = array{
 *     parent: string,
 *     rotate?:int|float|string,
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
 *      left?: int|float|string|null,
 *      top?: int|float|string|null,
 *      width?: int|float|string|null,
 *      height?: int|float|string|null,
 *      rotate?: int|float|string|null,
 *      hFlip?: bool|int|float|string|null,
 *      vFlip?: bool|int|float|string|null,
 *      provider?: string|null,
 * }
 * @phpstan-type TIconDefaults = array{
 *      left?:int|float|string,
 *      top?:int|float|string,
 *      width?:int|float|string,
 *      height?:int|float|string,
 *      rotate?:int|float|string,
 *      hFlip?:bool|int|float|string,
 *      vFlip?:bool|int|float|string,
 * }
 * @phpstan-type TIconData = array{
 *      icons: TIcons,
 *      aliases: TAliases,
 *      defaults?: TIconDefaults,
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
