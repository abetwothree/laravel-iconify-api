<?php

namespace AbeTwoThree\LaravelIconifyApi\Search;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Search\Contracts\SearchDriver;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\Filterable;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\ParsesKeywords;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\ParsesQuery;

/**
 * @phpstan-import-type TPrefixes from LaravelIconifyApi
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
class SearchFilesDriver implements SearchDriver
{
    use Filterable;
    use ParsesKeywords;
    use ParsesQuery;

    public function __construct(
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /** {@inheritDoc} */
    public function search(string $query): array
    {
        $this->filters['query'] = $query;
        $this->filters['keywords'] = $this->parseQuery($query);

        $prefixes = ! empty($this->filters['prefixes']) ? $this->filters['prefixes'] : LaravelIconifyApiFacade::prefixes();

        /** @var TPrefixes */
        $ignoredPrefixes = config()->array('iconify-api.search.ignored_prefixes');
        $prefixes = array_filter($prefixes, function ($prefix) use ($ignoredPrefixes) {
            return ! in_array($prefix, $ignoredPrefixes);
        });

        $prefixes = $this->filterByInfoValues($prefixes);

        // $results = $this->searchIcons($prefixes);

        return [
            'query' => $query,
            'driver' => 'files',
            'filters' => $this->filters,
        ];
    }

    /**
     * @param  TPrefixes  $prefixes
     * @return array<int,string>
     */
    protected function filterByInfoValues(array $prefixes): array
    {
        $infoColumns = ['category', 'palette', 'tags'];
        $searchInfo = false;

        foreach ($infoColumns as $column) {
            if (isset($this->filters[$column]) && ! empty($this->filters[$column])) {
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

        if (isset($this->filters['category']) && ! empty($this->filters['category'])) {

            if ($info['category'] !== $this->filters['category']) {
                return false;
            }
        }

        if (isset($this->filters['palette']) && ! empty($this->filters['palette'])) {
            if ($info['palette'] !== $this->filters['palette']) {
                return false;
            }
        }

        if (isset($this->filters['tags']) && ! empty($this->filters['tags'])) {
            if (! in_array($info['tags'], $this->filters['tags'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  TPrefixes  $prefixes
     * @return array<string>
     */
    // protected function searchIcons(array $prefixes): array
    // {
    //     $results = [];
    //     foreach ($prefixes as $prefix) {
    //         $results = array_merge($results, $this->searchFileIcons($prefix));
    //     }

    //     return $results;
    // }

    // protected function searchFileIcons(string $prefix): array
    // {
    //     $icons = $this->getIcons($prefix);

    //     $results = [];
    //     foreach ($icons as $icon) {
    //         if (str_contains($icon, $this->filters['query'])) {
    //             $results[] = $icon;
    //         }
    //     }

    //     return $results;
    // }
}
