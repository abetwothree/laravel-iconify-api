<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;

/**
 * @phpstan-import-type TPrefixes from LaravelIconifyApi
 */
trait FilterPrefixes
{
    /**
     * @param  TPrefixes  $prefixes
     * @return TPrefixes|array<void>
     */
    protected function filterPrefixesByInfoValues(array $prefixes): array
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
            if (! in_array(($info['tags'] ?? []), $this->filters['tags'], strict: true)) {
                return false;
            }
        }

        return true;
    }
}
