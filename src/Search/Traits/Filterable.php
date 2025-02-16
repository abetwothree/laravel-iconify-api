<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

/**
 * @phpstan-type TFilterBag = array{
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
     * @var TFilterBag
     */
    protected array $filterBag = [
        'query' => '',
        'page' => 1,
        'limit' => 100,
    ];

    /** {@inheritDoc} */
    public function prefixes(array $prefixes): static
    {
        $this->filterBag['prefixes'] = $prefixes;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->filterBag['limit'] = $limit;

        return $this;
    }

    public function page(int $page): static
    {
        $this->filterBag['page'] = $page;

        return $this;
    }

    public function category(string $category): static
    {
        $this->filterBag['category'] = $category;

        return $this;
    }

    public function similar(bool $similar): static
    {
        $this->filterBag['similar'] = $similar;

        return $this;
    }

    /** {@inheritDoc} */
    public function tags(array $tags): static
    {
        $this->filterBag['tags'] = $tags;

        return $this;
    }

    public function palette(bool $palette): static
    {
        $this->filterBag['palette'] = $palette;

        return $this;
    }

    public function style(string $style): static
    {
        $this->filterBag['style'] = $style;

        return $this;
    }
}
