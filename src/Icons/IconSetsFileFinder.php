<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use Exception;

class IconSetsFileFinder implements IconSetsFileFinderContract
{
    public function find(string $prefix, string $type = 'icons'): string
    {
        $singleSetFile = $this->getIconsFromIndividualSets($prefix, $type);
        $allSetsFile = $this->getIconsFromAllSets($prefix);

        if (is_string($singleSetFile)) {
            return $singleSetFile;
        }

        if (is_string($allSetsFile)) {
            return $allSetsFile;
        }

        throw new Exception("Could not find the icons file for the set: {$prefix}");
    }

    /**
     * This will try to find the path of the icons of individual icon set as "@iconify-json/mdi"
     */
    protected function getIconsFromIndividualSets(string $prefix, string $type): bool|string
    {
        $filePath = LaravelIconifyApi::singleSetJsonLocation($prefix, $type);

        if (! file_exists($filePath)) {
            return false;
        }

        return $filePath;
    }

    /**
     * This will try to find the icons file path from the full "@iconify/json" package
     */
    protected function getIconsFromAllSets(string $prefix): bool|string
    {
        $basePath = LaravelIconifyApi::fullSetsJsonLocation();
        $filepath = $basePath."/{$prefix}.json";

        if (! is_dir($basePath) || ! file_exists($filepath)) {
            return false;
        }

        return $filepath;
    }
}
