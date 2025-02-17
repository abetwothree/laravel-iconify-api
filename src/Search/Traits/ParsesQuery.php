<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi as LaravelIconifyApiFacade;
use Exception;

trait ParsesQuery
{
    /**
     * @return array<int,string>
     *
     * @throws Exception
     */
    protected function parseQuery(string $query, bool $allowPartial = true): array
    {
        // Split by space, check for prefixes and reserved keywords
        $keywordsChunks = explode(' ', trim(strtolower($query)));
        $keywords = [];

        $hasPrefixes = false;
        $checkPartial = false;
        $prefixList = LaravelIconifyApiFacade::prefixes();

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
                    $unmatchedPrefixes = array_diff($prefixes, $prefixList);
                    $prefixes = array_intersect($prefixes, $prefixList);

                    if (count($prefixes) > 0) {
                        $hasPrefixes = $this->addPrefixes($prefixes);
                    }

                    if (count($unmatchedPrefixes) > 0) {
                        $hasPrefixes = $this->addPartialPrefixes($unmatchedPrefixes);
                    }

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

        $keywords = $this->splitKeywordEntries($keywords, [
            'prefix' => ! $hasPrefixes && empty($this->filters['prefixes'] ?? []),
            'partial' => $checkPartial,
        ]);

        dd($keywords);

        return $keywords;
    }

    protected function parseKeywordValues(
        string $keyword,
        string $value,
        bool &$isKeyword,
        bool &$hasPrefixes
    ): void {
        $prefixList = LaravelIconifyApiFacade::prefixes();

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
                $unmatchedPrefixes = array_diff($prefixes, $prefixList);
                $prefixes = array_intersect($prefixes, $prefixList);

                if (count($prefixes) > 0) {
                    $isKeyword = $hasPrefixes = $this->addPrefixes($prefixes);
                }

                if (count($unmatchedPrefixes) > 0) {
                    $isKeyword = $hasPrefixes = $this->addPartialPrefixes($unmatchedPrefixes);
                }
                break;
        }
    }
}
