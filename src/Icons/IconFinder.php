<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;

/**
 * @phpstan-import-type TIconSetData from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TAlias from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TIconData from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
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

        /** @var TIconData $iconsSetInfo */
        $iconsSetInfo = [
            'icons' => [],
            'aliases' => [],
        ];

        /** @var array<string, TIconData> $iconsResponse */
        $iconsResponse = [];

        foreach ($icons as $icon) {
            $iconsResponse[$icon] = $iconsSetInfo;

            if (isset($iconsData['aliases'][$icon])) {
                $iconsResponse[$icon]['aliases'][$icon] = $iconsData['aliases'][$icon];
            }

            if (! isset($iconsData['icons'][$icon])) {

                // if not found, check if it's an alias, add the alias to the response
                if (isset($iconsData['aliases'][$icon])) {
                    /** @var TAlias $aliasData */
                    $aliasData = $iconsData['aliases'][$icon];

                    $parentIconName = $aliasData['parent'];

                    if (! isset($iconsData['icons'][$parentIconName])) {
                        if (! isset($iconsResponse[$icon]['not_found'])) {
                            $iconsResponse[$icon]['not_found'] = [];
                        }

                        $iconsResponse[$icon]['not_found'][] = $icon;

                        continue;
                    }

                    $iconsResponse[$icon]['icons'][$parentIconName] = $iconsData['icons'][$parentIconName];

                    continue;
                }

                if (! isset($iconsResponse[$icon]['not_found'])) {
                    $iconsResponse[$icon]['not_found'] = [];
                }

                $iconsResponse[$icon]['not_found'][] = $icon;

                continue;
            }

            $iconsResponse[$icon]['icons'][$icon] = $iconsData['icons'][$icon];
        }

        unset($iconsData);

        return $iconsResponse;
    }
}
