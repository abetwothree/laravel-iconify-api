<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

use Throwable;

trait FindsIconsInFileSets
{
    private array $addedIcons = [];

    private array $allMatches = [];

    private int $allMatchesLength = 0;

    protected function findIcons(): ?array
    {
        try {
            $this->runAllSearches(true);
            if ($this->allMatchesLength < $this->filters['limit']) {
                $this->runAllSearches(false);
            }
        } catch (Throwable $throwable) {
            report($throwable);

            return null;
        }

        return $this->generateResults();
    }

    private function runAllSearches(bool $isExact): void
    {
        foreach ($this->keywords->searches as $search) {
            $partial = $search->partial;
            if ($partial) {
                if ($isExact) {
                    if (isset($this->data->keywords[$partial])) {
                        $this->runSearch($search, true, $partial);
                    }
                } else {
                    $keywordsList = getPartialKeywords($partial, true, $this->data);
                    if ($keywordsList) {
                        foreach ($keywordsList as $keyword) {
                            $this->runSearch($search, false, $keyword);
                        }
                    }
                }
            } else {
                if (! $isExact) {
                    continue;
                }
                $this->runSearch($search, true);
            }

            if (! $isExact && $this->allMatchesLength >= $this->limit) {
                return;
            }
        }
    }

    private function runSearch(SearchKeywordsEntry $search, bool $isExact, ?string $partial = null): void
    {
        $filteredPrefixes = $this->getFilteredPrefixes($search, $partial);
        if (empty($filteredPrefixes)) {
            return;
        }

        $testKeywords = $partial ? array_merge($search->keywords, [$partial]) : $search->keywords;
        $testMatches = $search->test ? array_merge($search->test, $testKeywords) : $testKeywords;

        foreach ($filteredPrefixes as $prefix) {
            $this->processPrefixIcons($prefix, $search, $testKeywords, $testMatches, $isExact);
        }
    }

    private function getFilteredPrefixes(SearchKeywordsEntry $search, ?string $partial): array
    {
        if (isset($search->filteredPrefixes)) {
            return $search->filteredPrefixes;
        }

        $filteredPrefixes = $search->prefixes
            ? filterSearchPrefixesList($this->basePrefixes, $search->prefixes)
            : $this->basePrefixes;

        foreach ($search->keywords as $keyword) {
            $filteredPrefixes = array_filter(
                $filteredPrefixes,
                fn ($p) => isset($this->data->keywords[$keyword][$p])
            );
        }

        if ($partial) {
            $filteredPrefixes = array_filter(
                $filteredPrefixes,
                fn ($p) => isset($this->data->keywords[$partial][$p])
            );
        }

        $search->filteredPrefixes = $filteredPrefixes;

        return $filteredPrefixes;
    }

    private function processPrefixIcons(
        string $prefix,
        SearchKeywordsEntry $search,
        array $testKeywords,
        array $testMatches,
        bool $isExact
    ): void {
        $prefixAddedIcons = $this->addedIcons[$prefix] ??= [];
        $iconSet = $this->iconSets[$prefix]->item;
        $iconSetIcons = $iconSet->icons;
        $iconSetKeywords = $iconSetIcons->keywords ?? null;

        if (! $iconSetKeywords) {
            return;
        }

        $matches = $this->getKeywordMatches($testKeywords, $iconSetKeywords);
        if (! $matches) {
            return;
        }

        foreach ($matches as $item) {
            $this->processIconItem($item, $prefix, $prefixAddedIcons, $testMatches, $isExact);
        }
    }

    private function getKeywordMatches(array $testKeywords, object $iconSetKeywords): ?array
    {
        $matches = null;
        foreach ($testKeywords as $keyword) {
            if (! isset($iconSetKeywords->$keyword)) {
                return null;
            }

            $keywordMatches = iterator_to_array($iconSetKeywords->$keyword);
            $matches = $matches === null
                ? $keywordMatches
                : array_intersect($matches, $keywordMatches);
        }

        return $matches;
    }

    private function processIconItem(
        object $item,
        string $prefix,
        array &$prefixAddedIcons,
        array $testMatches,
        bool $isExact
    ): void {
        if (isset($prefixAddedIcons[spl_object_hash($item)])) {
            return;
        }

        if ($this->shouldSkipByStyle($item, $prefix)) {
            return;
        }

        $found = $this->findMatchingName($item, $testMatches, $prefix);
        if ($found) {
            $this->addIconToResults($found, $prefix, $item, $prefixAddedIcons, $isExact);
        }
    }

    private function shouldSkipByStyle(object $item, string $prefix): bool
    {
        return $this->fullParams['style'] &&
            $this->appConfig->allowFilterIconsByStyle &&
            $this->iconSets[$prefix]->item->icons->iconStyle === 'mixed' &&
            $item->_is !== $this->fullParams['style'];
    }

    private function findMatchingName(object $item, array $testMatches, string $prefix): ?string
    {
        foreach ($item as $index => $name) {
            foreach ($testMatches as $match) {
                if (strpos($name, $match) === false) {
                    continue 2;
                }
            }

            $length = $this->calculateNameLength($name, $item, $index, $prefix);

            return ['name' => $name, 'length' => $length];
        }

        return null;
    }

    private function calculateNameLength(string $name, object $item, int $index, string $prefix): int
    {
        if ($index === 0) {
            return $item->_l ?? strlen($name);
        }

        if ($this->iconSets[$prefix]->item->themeParts) {
            foreach ($this->iconSets[$prefix]->item->themeParts as $part) {
                if (str_starts_with($name, "$part-") || str_ends_with($name, "-$part")) {
                    return strlen($name) - strlen($part) - 1;
                }
            }
        }

        return strlen($name);
    }

    private function addIconToResults(
        array $found,
        string $prefix,
        object $item,
        array &$prefixAddedIcons,
        bool $isExact
    ): void {
        $prefixAddedIcons[spl_object_hash($item)] = true;
        $this->addedIcons[$prefix] = $prefixAddedIcons;

        $list = $this->getMatchResult($found['length'], ! $isExact);
        $list->names[] = "$prefix:{$found['name']}";
        $this->allMatchesLength++;

        if (! $isExact && $this->allMatchesLength >= $this->limit) {
            return;
        }
    }

    private function getMatchResult(int $length, bool $partial): TemporaryResultItem
    {
        foreach ($this->allMatches as $item) {
            if ($item->length === $length && $item->partial === $partial) {
                return $item;
            }
        }
        $newItem = new TemporaryResultItem($length, $partial);
        $this->allMatches[] = $newItem;

        return $newItem;
    }

    private function generateResults(): array
    {
        usort($this->allMatches, fn ($a, $b) => $a->partial !== $b->partial
                ? ($a->partial ? 1 : -1)
                : $a->length - $b->length
        );

        $results = [];
        $prefixes = [];
        foreach ($this->allMatches as $match) {
            foreach ($match->names as $name) {
                if (! $this->softLimit && count($results) >= $this->limit) {
                    break 2;
                }
                $results[] = $name;
                $prefixes[explode(':', $name)[0]] = true;
            }
        }

        return [
            'prefixes' => array_keys($prefixes),
            'names' => $results,
            'hasMore' => count($results) >= $this->limit,
        ];
    }
}
