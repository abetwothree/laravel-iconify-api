<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Contracts;

interface SearchDriver
{
    /** @return array<string,mixed> */
    public function search(string $query): array;

    /** @param array<int,string> $prefixes */
    public function prefixes(array $prefixes): static;

    public function limit(int $limit): static;

    public function page(int $page): static;

    public function category(string $category): static;

    public function similar(bool $similar): static;

    /** @param array<int,string> $tags */
    public function tags(array $tags): static;

    public function palette(bool $palette): static;

    public function style(string $style): static;
}
