<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;

/**
 * @phpstan-import-type TIconSetData from IconFinderContract
 */
class IconSetInfoFinder implements IconSetInfoFinderContract
{
    public function __construct(
        protected IconSetsFileFinderContract $iconSetsFileFinder
    ) {}

    /** {@inheritDoc} */
    public function find(string $set): array
    {
        $file = $this->iconSetsFileFinder->find($set);

        /** @var array<string, string|int> $content */
        $content = json_decode((string) file_get_contents($file), true);

        /** @var TIconSetData $data */
        $data = [
            'prefix' => $content['prefix'],
            'lastModified' => $content['lastModified'],
            'width' => $content['width'] ?? 0,
            'height' => $content['height'] ?? 0,
        ];

        return $data;
    }
}
