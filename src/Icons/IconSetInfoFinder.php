<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;


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

        /** @var TIconSetData $content */
        $content = json_decode((string) file_get_contents($file), true);

        return [
            'prefix' => $content['prefix'],
            'width' => $content['width'],
            'height' => $content['height'],
            'lastModified' => $content['lastModified'],
        ];
    }
}
