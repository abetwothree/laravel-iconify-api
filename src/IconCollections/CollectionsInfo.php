<?php

namespace AbeTwoThree\LaravelIconifyApi\IconCollections;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use Symfony\Component\Finder\Finder;

/**
 * @phpstan-import-type TIconCollectionInfo from CollectionInfo
 *
 * @phpstan-type TIconCollections = array<string, TIconCollectionInfo>
 */
class CollectionsInfo
{
    /**
     * @return TIconCollections
     */
    public function get(): array
    {
        $filePath = LaravelIconifyApi::iconsLocation().'/@iconify/json/collections.json';

        if (file_exists($filePath)) {
            /** @var TIconCollections */
            $data = json_decode((string) file_get_contents($filePath), true);
        } else {
            $data = $this->gatherFromIndividualSets();
        }

        return $data;
    }

    /**
     * @return TIconCollections
     */
    protected function gatherFromIndividualSets(): array
    {
        $folderPath = LaravelIconifyApi::iconsLocation().'/@iconify-json';

        $finder = new Finder;
        $finder->directories()->in($folderPath);
        $collections = [];

        foreach ($finder as $dir) {
            $metadataPath = $dir->getRealPath().'/info.json';

            if (! file_exists($metadataPath)) {
                continue;
            }

            /** @var TIconCollectionInfo */
            $metadata = json_decode((string) file_get_contents($metadataPath), true);

            $name = $metadata['prefix'] ?? $dir->getFilename();
            $collections[$name] = $metadata;
        }

        return $collections;
    }
}
