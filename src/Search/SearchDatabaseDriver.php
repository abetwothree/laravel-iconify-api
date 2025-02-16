<?php

namespace AbeTwoThree\LaravelIconifyApi\Search;

use AbeTwoThree\LaravelIconifyApi\Search\Contracts\SearchDriver;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\Filterable;

class SearchDatabaseDriver implements SearchDriver
{
    use Filterable;

    /** {@inheritDoc} */
    public function search(string $query): array
    {
        return [
            'query' => $query,
            'driver' => 'files',
            'filters' => $this->filters,
        ];
    }
}
