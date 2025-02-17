<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;

/**
 * @phpstan-import-type TPrefixes from LaravelIconifyApi
 * @phpstan-import-type TKeywordResults from ParsesKeywords
 *
 * @phpstan-type TTags = array<int,string>
 * @phpstan-type TFilters = array{
 *  query: string,
 *  search?: string,
 *  keywords: TKeywordResults|array<void>|null,
 *  page: int,
 *  limit: int,
 *  prefixes?: TPrefixes,
 *  category?: string,
 *  similar?: bool,
 *  tags?: TTags,
 *  palette?: bool,
 *  style?: string,
 * }
 */
trait Filterable
{
    /**
     * @var TFilters
     */
    protected array $filters = [
        'query' => '',
        'keywords' => null,
        'page' => 1,
        'limit' => 100,
    ];

    /** {@inheritDoc} */
    public function prefixes(array $prefixes): static
    {
        $this->filters['prefixes'] = [];
        $this->processPrefixes($prefixes);

        return $this;
    }

    /**
     * @param  TPrefixes  $prefixes
     */
    public function processPrefixes(array $prefixes): bool
    {
        $prefixList = LaravelIconifyApiFacade::prefixes();
        $matchedPrefixes = array_intersect($prefixes, $prefixList);
        $unmatchedPrefixes = array_diff($prefixes, $prefixList);

        $hasNewPrefixes = false;
        $hasPartialPrefixes = false;

        if (count($matchedPrefixes) > 0) {
            $hasNewPrefixes = $this->addPrefixes($matchedPrefixes);
        }

        if (count($unmatchedPrefixes) > 0) {
            $hasPartialPrefixes = $this->addPartialPrefixes($unmatchedPrefixes);
        }

        if ($hasNewPrefixes || $hasPartialPrefixes) {
            return true;
        }

        return false;
    }

    /**
     * @param  TPrefixes  $prefixes
     */
    public function addPrefixes(array $prefixes): bool
    {
        $prefixes = array_intersect($prefixes, LaravelIconifyApiFacade::prefixes());
        $prefixes = array_merge($prefixes, ($this->filters['prefixes'] ?? []));

        if (! empty($prefixes)) {
            $this->filters['prefixes'] = array_values(array_unique($prefixes));

            return true;
        }

        return false;
    }

    /**
     * @param  TPrefixes  $prefixes
     */
    public function addPartialPrefixes(array $prefixes): bool
    {
        $prefixList = LaravelIconifyApiFacade::prefixes();
        $prefixesToAdd = [];

        foreach ($prefixes as $prefix) {

            if (str_ends_with($prefix, '*')) {
                $prefix = substr($prefix, 0, -1);
            }

            $prefixesToAdd = array_merge($prefixesToAdd, array_filter(
                $prefixList,
                fn ($p) => str_starts_with($p, $prefix)
            ));
        }

        $prefixesToAdd = array_values(array_unique($prefixesToAdd));

        if (! empty($prefixesToAdd)) {
            $this->addPrefixes($prefixesToAdd);

            return true;
        }

        return false;
    }

    public function limit(int $limit): static
    {
        $this->filters['limit'] = $limit;

        return $this;
    }

    public function page(int $page): static
    {
        $this->filters['page'] = $page;

        return $this;
    }

    public function category(string $category): static
    {
        $this->filters['category'] = $category;

        return $this;
    }

    public function similar(bool $similar): static
    {
        $this->filters['similar'] = $similar;

        return $this;
    }

    /** {@inheritDoc} */
    public function tags(array $tags): static
    {
        $this->filters['tags'] = $tags;

        return $this;
    }

    public function palette(bool $palette): static
    {
        $this->filters['palette'] = $palette;

        return $this;
    }

    public function style(string $style): static
    {
        $this->filters['style'] = $style;

        return $this;
    }
}
