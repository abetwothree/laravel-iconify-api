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

            if (isset($iconsData['icons'][$icon])) {
                $iconsResponse[$icon]['icons'][$icon] = $iconsData['icons'][$icon];

                continue;
            }

            if ($this->appendAliasChain($iconsData, $iconsResponse[$icon], $icon, [])) {
                continue;
            }

            if (! isset($iconsResponse[$icon]['not_found'])) {
                $iconsResponse[$icon]['not_found'] = [];
            }

            $iconsResponse[$icon]['not_found'][] = $icon;
        }

        unset($iconsData);

        return $iconsResponse;
    }

    /**
     * @param  TIconSetData  $iconsData
     * @param  TIconData  $iconResponse
     * @param  array<int, string>  $visited
     */
    protected function appendAliasChain(array $iconsData, array &$iconResponse, string $name, array $visited): bool
    {
        if (in_array($name, $visited, true) || ! isset($iconsData['aliases'][$name])) {
            return false;
        }

        $visited[] = $name;

        /** @var TAlias $alias */
        $alias = $iconsData['aliases'][$name];
        $iconResponse['aliases'][$name] = $alias;

        $parent = $alias['parent'];

        if (isset($iconsData['icons'][$parent])) {
            $iconResponse['icons'][$parent] = $iconsData['icons'][$parent];

            return true;
        }

        return $this->appendAliasChain($iconsData, $iconResponse, $parent, $visited);
    }
}
