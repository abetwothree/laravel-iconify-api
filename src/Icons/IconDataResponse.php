<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;

/**
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 * @phpstan-import-type TIconData from IconFinderContract
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
class IconDataResponse
{
    public function __construct(
        protected IconFinderContract $iconFinder,
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /**
     * Summary of response
     *
     * @param  array<int, string>  $icons
     * @return TIconResponse
     */
    public function get(string $set, array $icons): array
    {
        $iconInfo = $this->iconSetInfoFinder->find($set);
        $foundIcons = $this->iconFinder->find($set, $icons);

        return $this->flattenIconResponse($iconInfo, $foundIcons);
    }

    /**
     * @param  TIconSetInfo  $iconSetInfo
     * @param  array<string, TIconData>  $icons
     * @return TIconResponse
     */
    protected function flattenIconResponse(array $iconSetInfo, array $icons): array
    {
        $iconSetInfo['icons'] = [];
        $iconSetInfo['aliases'] = [];
        $iconSetInfo['not_found'] = [];

        foreach ($icons as $icon) {
            $iconSetInfo['aliases'] = array_merge($iconSetInfo['aliases'], $icon['aliases']);
            $iconSetInfo['icons'] = array_merge($iconSetInfo['icons'], $icon['icons']);
            $iconSetInfo['not_found'] = array_merge($iconSetInfo['not_found'], ($icon['not_found']?? []));
        }

        return $iconSetInfo;
    }
}
