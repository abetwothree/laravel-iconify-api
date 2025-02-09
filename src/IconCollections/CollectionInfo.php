<?php

namespace AbeTwoThree\LaravelIconifyApi\IconCollections;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use Exception;

/**
 * @phpstan-type TIconCollectionInfo array{
 *     name: string,
 *     total: int,
 *     author: array{name:string, url:string},
 *     license: array{title:string, spdx:string, url:string},
 *     samples: array<int,string>,
 *     palette: bool,
 *     height?: int,
 *     category?: string,
 *     hidden?: bool,
 *     tags?: array<int,string>,
 *     prefix?: string,
 *  }
 * @phpstan-type TIconCollections = array<string, TIconCollectionInfo>
 */
class CollectionInfo
{
    public function __construct(
        protected CollectionsInfo $collectionsInfo
    ) {}

    /**
     * @return TIconCollectionInfo
     */
    public function get(string $set): array
    {
        $infoFilePath = LaravelIconifyApi::iconsLocation().'/@iconify-json/'.$set.'/info.json';

        if (file_exists($infoFilePath)) {
            /** @var TIconCollectionInfo */
            $data = json_decode((string) file_get_contents($infoFilePath), true);
        } else {
            $collections = $this->collectionsInfo->get();

            if (! array_key_exists($set, $collections)) {
                throw new Exception("Could not find the collection: {$set}");
            }

            $data = $collections[$set];
        }

        return $data;
    }
}
