<?php

namespace AbeTwoThree\LaravelIconifyApi\Search;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Search\Contracts\SearchDriver;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\Filterable;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\FilterPrefixes;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\ParsesKeywords;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\ParsesQuery;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\FindsIconsInFileSets;

/**
 * @phpstan-import-type TPrefixes from LaravelIconifyApi
 * @phpstan-import-type TIconSetInfo from IconSetInfoFinderContract
 */
class SearchFilesDriver implements SearchDriver
{
    use Filterable;
    use FilterPrefixes;
    use ParsesKeywords;
    use ParsesQuery;
    use FindsIconsInFileSets;

    public function __construct(
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /** {@inheritDoc} */
    public function search(string $query): array
    {
        $this->filters['query'] = $query;
        $this->filters['keywords'] = $this->parseQuery(
            $query,
            $this->filters['partial'] = $this->filters['similar'] ?? true
        );

        $icons = null;
        if ($this->filters['keywords']) {
            $icons = $this->findIcons();
        }

        return [
            'driver' => 'files',
            'icons' => $icons,
            'request' => $this->filters,
        ];
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
