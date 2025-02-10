<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use Exception;

class IconSetsFileFinder implements IconSetsFileFinderContract
{
    public function find(string $set): string
    {
        $singleSetFile = $this->getIconsFromIndividualSets($set);
        $allSetsFile = $this->getIconsFromAllSets($set);

        if (is_string($singleSetFile)) {
            return $singleSetFile;
        }

        if (is_string($allSetsFile)) {
            return $allSetsFile;
        }

        throw new Exception("Could not find the icons file for the set: {$set}");
    }

    /**
     * This will try to find the path of the icons of individual icon set as "@iconify-json/mdi"
     */
    protected function getIconsFromIndividualSets(string $set): bool|string
    {
        $folderPath = LaravelIconifyApi::singleSetLocation().'/'.$set;
        $filePath = $folderPath.'/icons.json';

        if (! is_dir($folderPath) || ! file_exists($filePath)) {
            return false;
        }

        return $filePath;
    }

    /**
     * This will try to find the icons file path from the full "@iconify/json" package
     */
    protected function getIconsFromAllSets(string $set): bool|string
    {
        $basePath = LaravelIconifyApi::fullSetLocation().'/json/json';
        $filepath = $basePath."/{$set}.json";

        if (! is_dir($basePath) || ! file_exists($filepath)) {
            return false;
        }

        return $filepath;
    }
}
