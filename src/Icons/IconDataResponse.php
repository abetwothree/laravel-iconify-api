<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoSummaryFinder as IconSetInfoSummaryFinderContract;

/**
 * @phpstan-import-type TIconSetInfoSummary from IconSetInfoSummaryFinderContract
 * @phpstan-import-type TIconData from IconFinderContract
 * @phpstan-import-type TIcons from IconFinderContract
 * @phpstan-import-type TAliases from IconFinderContract
 * @phpstan-import-type TNotFound from IconFinderContract
 *
 * @phpstan-type TIconResponse = array{
 *      prefix: string,
 *      lastModified: int,
 *      width?: int,
 *      height?: int,
 *      aliases: TAliases,
 *      icons: TIcons,
 *      not_found?: TNotFound,
 * }
 */
class IconDataResponse
{
    public function __construct(
        protected IconFinderContract $iconFinder,
        protected IconSetInfoSummaryFinderContract $iconSetInfoSummaryFinder,
    ) {}

    /**
     * @param  array<int, string>  $icons
     * @return TIconResponse
     */
    public function get(string $prefix, array $icons): array
    {
        $iconSetInfo = $this->iconSetInfoSummaryFinder->find($prefix);
        $foundIcons = $this->iconFinder->find($prefix, $icons);

        return $this->flattenIconResponse($iconSetInfo, $foundIcons);
    }

    /**
     * @param  TIconSetInfoSummary  $iconSetInfo
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
            $iconSetInfo['not_found'] = array_merge($iconSetInfo['not_found'], ($icon['not_found'] ?? []));
        }

        return $iconSetInfo;
    }
}
