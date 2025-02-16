<?php

namespace AbeTwoThree\LaravelIconifyApi\Search;

use AbeTwoThree\LaravelIconifyApi\Search\Contracts\SearchDriver;
use AbeTwoThree\LaravelIconifyApi\Search\Traits\Filterable;

class SearchFilesDriver implements SearchDriver
{
    use Filterable;

    public function __construct(
        protected FileSearcher $fileSearcher
    ) {}

    /** {@inheritDoc} */
    public function search(string $query): array
    {
        $this->filterBag['query'] = $query;

        $this->fileSearcher->search($this->filterBag);

        return [
            'query' => $query,
            'driver' => 'files',
            'filters' => $this->filterBag,
        ];
    }
}
