<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

/**
 * @phpstan-type TFilters = array{
 *  query: string,
 *  page: int,
 *  limit: int,
 *  prefixes?: array<int,string>,
 *  category?: string,
 *  similar?: bool,
 *  tags?: array<int,string>,
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
        'page' => 1,
        'limit' => 100,
    ];

    /** {@inheritDoc} */
    public function prefixes(array $prefixes): static
    {
        $this->filters['prefixes'] = $prefixes;

        return $this;
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
