<?php

namespace AbeTwoThree\LaravelIconifyApi\Search;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\Filterable;

/**
 * @phpstan-import-type TFilterBag from Filterable
 * @phpstan-import-type TPrefixes from LaravelIconifyApi
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
class FileSearcher
{
    /**
     * @var TFilterBag|null
     */
    protected ?array $filterBag = null;

    public function __construct(
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /**
     * @param  TFilterBag  $filterBag
     */
    public function search(array $filterBag): void
    {
        $this->filterBag = $filterBag;

        $prefixes = ! empty($filterBag['prefixes']) ? $filterBag['prefixes'] : LaravelIconifyApiFacade::prefixes();

        /** @var TPrefixes */
        $ignoredPrefixes = config()->array('iconify-api.search.ignored_prefixes');
        $prefixes = array_filter($prefixes, function ($prefix) use ($ignoredPrefixes) {
            return ! in_array($prefix, $ignoredPrefixes);
        });

        $prefixes = $this->filterByInfoValues($prefixes);

        // search icons in the remaining prefixes

    }

    /**
     * @param  TPrefixes  $prefixes
     * @return array<string>
     */
    protected function filterByInfoValues(array $prefixes): array
    {
        $infoColumns = ['category', 'palette', 'tags'];
        $searchInfo = false;

        foreach ($infoColumns as $column) {
            if (isset($this->filterBag[$column]) && ! empty($this->filterBag[$column])) {
                $searchInfo = true;
                break;
            }
        }

        if ($searchInfo === false) {
            return $prefixes;
        }

        $filteredPrefixes = [];
        foreach ($prefixes as $prefix) {
            if ($this->searchIconSetInfo($prefix)) {
                $filteredPrefixes[] = $prefix;
            }
        }

        return $filteredPrefixes;
    }

    protected function searchIconSetInfo(string $prefix): bool
    {
        $info = $this->iconSetInfoFinder->find($prefix);

        if (isset($this->filterBag['category']) && ! empty($this->filterBag['category'])) {

            if ($info['category'] !== $this->filterBag['category']) {
                return false;
            }
        }

        if (isset($this->filterBag['palette']) && ! empty($this->filterBag['palette'])) {
            if ($info['palette'] !== $this->filterBag['palette']) {
                return false;
            }
        }

        if (isset($this->filterBag['tags']) && ! empty($this->filterBag['tags'])) {
            if (! in_array($info['tags'], $this->filterBag['tags'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int,string>  $files
     * @return array<string>
     */
    protected function searchIcons(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            // $fileResult = $this->searchFileIcons($file);
        }

        return $results;
    }
}
