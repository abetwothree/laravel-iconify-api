<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;

/**
 * @phpstan-import-type TIconSetData from IconFinderContract
 */
class IconFinder implements IconFinderContract
{
    public function __construct(
        protected IconSetsFileFinderContract $iconSetsFileFinder
    ) {}

    /** {@inheritDoc} */
    public function find(string $set, array $icons): array
    {
        $iconFile = $this->iconSetsFileFinder->find($set);
        $contents = file_get_contents($iconFile);
        assert($contents !== false);

        /** @var TIconSetData $iconsData */
        $iconsData = json_decode($contents, true);

        $iconsSetInfo = [
            'prefix' => $iconsData['prefix'],
            'lastModified' => $iconsData['lastModified'],
            'width' => $iconsData['width'],
            'height' => $iconsData['height'],
            'aliases' => [],
            'icons' => [],
        ];

        $iconsResponse = [];

        foreach ($icons as $icon) {
            $iconsResponse[$icon] = $iconsSetInfo;

            if (isset($iconsData['aliases'][$icon])) {
                $iconsResponse[$icon]['aliases'][$icon] = $iconsData['aliases'][$icon];
            }

            if (! isset($iconsData['icons'][$icon])) {
                $iconsResponse[$icon]['not_found'][] = $icon;

                continue;
            }

            $iconsResponse[$icon]['icons'][$icon] = $iconsData['icons'][$icon];
        }

        unset($contents, $iconsData);

        return $iconsResponse;
    }
}
