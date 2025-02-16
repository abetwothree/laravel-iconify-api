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

        if (isset($content['width']) && ! empty($content['width'])) {
            $data['width'] = $content['width'];
        }

        if (isset($content['height']) && ! empty($content['height'])) {
            $data['height'] = $content['height'];
        }

        unset($content);

        return $data;
    }
}
