<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;

/**
 * @phpstan-import-type TIconResponse from IconFinderContract
 */
class IconDataResponse
{
    public function __construct(
        protected IconFinderContract $iconFinder
    ) {}

    /**
     * Summary of response
     *
     * @param array<int, string> $icons
     * @return TIconResponse
     */
    public function get(string $set, array $icons): array
    {
        $foundIcons = $this->findIcons($set, $icons);

        return $this->flattenIconResponse($foundIcons);
    }

    /**
     * @param array<int, string> $icons
     * @return array<string, TIconResponse>
     */
    protected function findIcons(string $set, array $icons): array
    {
        return $this->iconFinder->find($set, $icons);
    }

    /**
     * @param array<string, TIconResponse> $icons
     * @return TIconResponse
     */
    protected function flattenIconResponse(array $icons): array
    {
        $flattenedIcons = [
            'prefix' => '',
            'lastModified' => 0,
            'width' => 0,
            'height' => 0,
            'aliases' => [],
            'icons' => [],
        ];

        foreach ($icons as $icon) {
            $flattenedIcons['prefix'] = $icon['prefix'];
            $flattenedIcons['lastModified'] = $icon['lastModified'];
            $flattenedIcons['width'] = $icon['width'];
            $flattenedIcons['height'] = $icon['height'];
            $flattenedIcons['aliases'] = array_merge($flattenedIcons['aliases'], $icon['aliases']);
            $flattenedIcons['icons'] = array_merge($flattenedIcons['icons'], $icon['icons']);
        }

        return $flattenedIcons;
    }
}
