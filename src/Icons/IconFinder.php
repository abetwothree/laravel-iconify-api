<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use pcrov\JsonReader\JsonReader;

/**
 * @phpstan-import-type TIconSetData from IconFinderContract
 * @phpstan-import-type TIcon from IconFinderContract
 * @phpstan-import-type TIcons from IconFinderContract
 * @phpstan-import-type TAlias from IconFinderContract
 * @phpstan-import-type TAliases from IconFinderContract
 */
class IconFinder implements IconFinderContract
{
    public function __construct(
        protected IconSetsFileFinderContract $iconSetsFileFinder
    ) {}

    /** {@inheritDoc} */
    public function find(string $prefix, array $icons): array
    {
        $iconFile = $this->iconSetsFileFinder->find($prefix);

        /** @var TIconSetData $iconsData */
        $iconsData = json_decode((string) file_get_contents($iconFile), true);

        $iconsSetInfo = [
            'icons' => [],
            'aliases' => [],
        ];

        $iconsResponse = [];

        foreach ($icons as $icon) {
            $iconsResponse[$icon] = $iconsSetInfo;

            if (isset($iconsData['aliases'][$icon])) {
                $iconsResponse[$icon]['aliases'][$icon] = $iconsData['aliases'][$icon];
            }

            if (! isset($iconsData['icons'][$icon])) {

                // if not found, check if it's an alias, add the alias to the response
                if(isset($iconsData['aliases'][$icon])) {
                    $parentIconName = $iconsData['aliases'][$icon]['parent'];
                    $iconsResponse[$icon]['icons'][$parentIconName] = $iconsData['icons'][$parentIconName];
                }else{
                    $iconsResponse[$icon]['not_found'][] = $icon;
                }

                continue;
            }

            $iconsResponse[$icon]['icons'][$icon] = $iconsData['icons'][$icon];
        }

        unset($iconsData);

        return $iconsResponse;
    }
}
