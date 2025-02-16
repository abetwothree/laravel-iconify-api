<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder;
use pcrov\JsonReader\JsonReader;

/**
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
class IconSetInfoFinder implements IconSetInfoFinderContract
{
    public function __construct(
        protected IconSetsFileFinder $fileFinder,
    ) {}

    /** {@inheritDoc} */
    public function find(string $prefix): array
    {
        $file = $this->fileFinder->find($prefix, 'info');

        $reader = new JsonReader;
        $reader->open($file);

        if (str_contains($file, LaravelIconifyApi::singleSetLocation())) {
            $reader->read();
        } else {
            $reader->read('info');
        }

        /** @var TIconSetInfo $info */
        $info = $reader->value();

        if(! isset($info['prefix'])) {
            $info['prefix'] = $prefix;
        }

        $reader->close();

        return $info;
    }
}
