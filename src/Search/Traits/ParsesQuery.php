<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApi;
use Exception;

/**
 * @phpstan-import-type TPrefixes from LaravelIconifyApi
 * @phpstan-import-type TKeywordResults from ParsesKeywords
 * @phpstan-import-type TKeywords from ParsesKeywords
 * @phpstan-import-type TTest from ParsesKeywords
 *
 * @phpstan-type TSearchKeywordsEntry = array{
 *   keywords:TKeywords,
 *   prefixes:TPrefixes|array<void>,
 *   test:TTest|null,
 *   partial:string|null
 * }
 * @phpstan-type TSearchKeywords = array<int, TSearchKeywordsEntry>
 */
trait ParsesQuery
{
    /**
     * @return TSearchKeywords|array<void>|null
     */
    protected function parseQuery(string $query, bool $allowPartial = true): ?array
    {
        // Split by space, check for prefixes and reserved keywords
        $keywordsChunks = explode(' ', trim(strtolower($query)));
        $keywords = [];

        $hasPrefixes = false;
        $checkPartial = false;

        foreach ($keywordsChunks as $chunk) {
            $prefixChunks = explode(':', $chunk);
            if (count($prefixChunks) > 2) {
                throw new Exception('Invalid query. Too many prefixes');
            }

            if (count($prefixChunks) === 2) {
                $keyword = $prefixChunks[0];
                $value = $prefixChunks[1];
                $isKeyword = false;

                $this->parseKeywordValues($keyword, $value, $isKeyword, $hasPrefixes);

                if (! $isKeyword) {
                    $prefixes = explode(',', $keyword);
                    $hasPrefixes = $this->processPrefixes($prefixes);

                    if ($value) {
                        $keywords[] = $value;
                        $checkPartial = $allowPartial;
                    }
                }

                continue;
            }

            $paramChunks = explode('=', $chunk);
            if (count($paramChunks) > 2) {
                throw new Exception('Invalid query. Too many parameters');
            }

            if (count($paramChunks) === 2) {
                $keyword = $paramChunks[0];
                $value = $paramChunks[1];
                $isKeyword = false;

                $this->parseKeywordValues($keyword, $value, $isKeyword, $hasPrefixes);

                if (! $isKeyword) {
                    $keywords[] = $chunk;
                }

                continue;
            }

            $keywords[] = $chunk;
            $checkPartial = $allowPartial;

            continue;
        }

        $entries = $this->splitKeywordEntries($keywords, [
            'prefix' => ! $hasPrefixes && empty($this->filters['prefixes'] ?? []),
            'partial' => $checkPartial,
        ]);

        if (empty($entries)) {
            return null;
        }

        $prefixes = ! empty($this->filters['prefixes'])
            ? $this->filters['prefixes']
            : LaravelIconifyApiFacade::prefixes();

        /** @var TPrefixes */
        $ignoredPrefixes = config()->array('iconify-api.search.ignored_prefixes');
        $prefixes = array_filter(
            $prefixes,
            fn ($prefix) => ! in_array($prefix, $ignoredPrefixes)
        );

        $prefixes = $this->filterPrefixesByInfoValues($prefixes);

        $this->filters['matched_prefixes'] = $prefixes;

        if (empty($prefixes)) {
            return null;
        }

        /** @var TSearchKeywords $searches */
        $searches = collect($entries)
            ->filter(fn ($entry): bool => is_array($entry) && ! empty($entry))
            ->map(fn ($entry): array => [
                ...$entry,
                'prefixes' => isset($entry['prefix'])
                ? array_merge($prefixes, [$entry['prefix']])
                : $prefixes,
            ])
            ->values()
            ->toArray();

       $prefixes = collect($searches)
                ->map(fn ($search) => $search['prefixes'])
                ->flatten()
                ->merge($prefixes)
                ->unique()
                ->sort()
                ->values()
                ->toArray();

        $this->filters['prefixes'] = $prefixes;

        return $searches;
    }

    protected function parseKeywordValues(
        string $keyword,
        string $value,
        bool &$isKeyword,
        bool &$hasPrefixes
    ): void {
        switch ($keyword) {
            case 'palette':
                $palette = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($palette !== null) {
                    $this->filters['palette'] = $palette;
                    $isKeyword = true;
                }
                break;
            case 'style':
                if (in_array($value, ['fill', 'stroke'])) {
                    $this->filters['style'] = $value;
                    $isKeyword = true;
                }
                break;
            case 'fill':
                $fill = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($fill !== null) {
                    $this->filters['style'] = 'fill';
                    $isKeyword = true;
                }
                break;
            case 'stroke':
                $stroke = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($stroke !== null) {
                    $this->filters['style'] = 'stroke';
                    $isKeyword = true;
                }
                break;
            case 'prefix':
            case 'prefixes':
                $prefixes = explode(',', $value);
                $isKeyword = $hasPrefixes = $this->processPrefixes($prefixes);
                break;
            case 'category':
                $this->filters['category'] = $value;
                $isKeyword = true;
                break;
            case 'tags':
                $tags = explode(',', $value);
                $this->filters['tags'] = $tags;
                $isKeyword = true;
                break;
        }
    }
}
