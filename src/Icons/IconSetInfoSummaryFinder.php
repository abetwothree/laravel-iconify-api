<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoSummaryFinder as IconSetInfoSummaryFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;

/**
 * @phpstan-import-type TIconSetData from IconFinderContract
 * @phpstan-import-type TIconSetInfoSummary from IconSetInfoSummaryFinderContract
 */
class IconSetInfoSummaryFinder implements IconSetInfoSummaryFinderContract
{
    /**
     * Icon set root properties copied verbatim onto an icon set response.
     *
     * Mirrors `propsToCopy` in packages/utils/src/icon-set/get-icons.ts:8-10 —
     * the keys of `defaultIconDimensions` plus `provider`. Dropping `left`/`top`
     * makes a client rebuild the wrong viewBox for any set with a negative origin.
     *
     * @var array<int, string>
     */
    protected const PROPS_TO_COPY = ['left', 'top', 'width', 'height', 'provider'];

    public function __construct(
        protected IconSetsFileFinderContract $iconSetsFileFinder
    ) {}

    /** {@inheritDoc} */
    public function find(string $prefix): array
    {
        $file = $this->iconSetsFileFinder->find($prefix);

        /** @var TIconSetData $content */
        $content = json_decode((string) file_get_contents($file), true);

        /** @var TIconSetInfoSummary $data */
        $data = [
            'prefix' => $content['prefix'],
            'lastModified' => $content['lastModified'],
        ];

        foreach (self::PROPS_TO_COPY as $property) {
            if (array_key_exists($property, $content) && $content[$property] !== null) {
                $data[$property] = $content[$property];
            }
        }

        unset($content);

        return $data;
    }
}
