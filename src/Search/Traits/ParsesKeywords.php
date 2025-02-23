<?php

namespace AbeTwoThree\LaravelIconifyApi\Search\Traits;

/**
 * @phpstan-type TKeywords = array<int|string, int|string|true>|null
 * @phpstan-type TTest = non-empty-array<int|non-falsy-string, int|string|true>|null
 * @phpstan-type TKeywordsSet = array{
 *  keywords?:TKeywords,
 *  partial?:string|null,
 *  prefix?:string|null,
 *  test?:TTest,
 * }
 * @phpstan-type TKeywordEntry = array{value:string, empty:bool}
 * @phpstan-type TKeywordResults = non-empty-array<string|int, TKeywordsSet>
 */
trait ParsesKeywords
{
    /**
     * @param  array<int,string>  $values
     * @param  array<string,bool|int|string>  $options
     * @return TKeywordResults|array<void>|null
     */
    protected function splitKeywordEntries(array $values, array $options): ?array
    {
        $results = [];
        $invalid = false;
        $splitValues = [];
        $iconPattern = '/^[a-z0-9]+(-[a-z0-9]+)*$/';
        $minPartialLength = isset($options['minPartialKeywordLength']) ? (int) $options['minPartialKeywordLength'] : 2;

        // Process input values
        foreach ($values as $item) {
            $entries = [];
            $hasValue = false;

            $parts = explode('-', $item);
            foreach ($parts as $part) {
                $empty = empty($part);
                if (! $empty && ! preg_match($iconPattern, $part)) {
                    $invalid = true;
                    break;
                }

                $entries[] = ['value' => $part, 'empty' => $empty];
                $hasValue = $hasValue || ! $empty;
            }

            $splitValues[] = $entries;
            if (! $hasValue) {
                $invalid = true;
            }
        }

        if ($invalid || empty($splitValues)) {
            return null;
        }

        $lastIndex = count($splitValues) - 1;

        // Process prefix options
        if ($options['prefix'] ?? false) {
            $firstItem = $splitValues[0];
            $firstItemCount = count($firstItem);

            // Handle first keyword as prefix
            if ($lastIndex > 0) {
                $emptyPos = null;
                foreach ($firstItem as $i => $entry) {
                    if ($entry['empty']) {
                        $emptyPos = $i;
                        break;
                    }
                }

                if ($emptyPos === null || ($firstItemCount > 1 && $emptyPos === $firstItemCount - 1)) {
                    $prefix = $this->keywordValuesToString($firstItem) ?? $firstItem[0]['value'];

                    if (! empty($prefix)) {
                        $set = $this->createKeywordSet();
                        for ($i = 1; $i <= $lastIndex; $i++) {
                            $this->processKeywordEntries(
                                $splitValues[$i],
                                $set,
                                ($options['partial'] ?? false) && ($i === $lastIndex),
                                $minPartialLength,
                            );
                        }
                        $this->addKeywordResult($results, $set, $prefix);
                    }
                }
            }

            // Handle partial first keyword as prefix
            if ($firstItemCount > 1 && ! $firstItem[0]['empty'] && ! $firstItem[1]['empty']) {
                $modifiedItem = array_slice($firstItem, 1);
                $prefix = $firstItem[0]['value'];

                $set = $this->createKeywordSet();
                for ($i = 0; $i <= $lastIndex; $i++) {
                    $entries = ($i === 0) ? $modifiedItem : $splitValues[$i];
                    $this->processKeywordEntries(
                        $entries,
                        $set,
                        ($options['partial'] ?? false) && ($i === $lastIndex),
                        $minPartialLength,
                    );
                }
                $this->addKeywordResult($results, $set, $prefix);
            }
        }

        // Process default case
        $defaultSet = $this->createKeywordSet();
        foreach ($splitValues as $i => $entries) {
            $this->processKeywordEntries(
                $entries,
                $defaultSet,
                ($options['partial'] ?? false) && ($i === $lastIndex),
                $minPartialLength
            );
        }
        $this->addKeywordResult($results, $defaultSet);

        // Process merges
        if (count($splitValues) > 1) {
            $validIndexes = array_keys(array_filter($splitValues, fn ($e) => count($e) === 1 && ! $e[0]['empty']));

            if (count($validIndexes) > 1) {
                for ($start = 0; $start < $lastIndex; $start++) {
                    if (! in_array($start, $validIndexes)) {
                        continue;
                    }

                    for ($end = $start + 1; $end <= $lastIndex; $end++) {
                        if (! in_array($end, $validIndexes)) {
                            break;
                        }

                        $merged = implode('',
                            array_column(
                                array_slice($splitValues, $start, $end - $start + 1),
                                '0.value'
                            )
                        );

                        $newValues = array_merge(
                            array_slice($splitValues, 0, $start),
                            [[['value' => $merged, 'empty' => false]]],
                            array_slice($splitValues, $end + 1)
                        );

                        $mergeSet = $this->createKeywordSet();
                        foreach ($newValues as $i => $entries) {
                            $this->processKeywordEntries(
                                $entries,
                                $mergeSet,
                                ($options['partial'] ?? false) && ($i === count($newValues) - 1),
                                $minPartialLength
                            );
                        }
                        $this->addKeywordResult($results, $mergeSet);
                    }
                }
            }
        }

        $prefixes = array_values(array_map(
            fn ($result) => $result['prefix'],
            array_filter(
                $results,
                fn ($result) => isset($result['prefix']) && ! empty($result['prefix'])
            )
        ));

        if (! empty($prefixes)) {
            $this->processPrefixes($prefixes);
        }

        return $results;
    }

    /**
     * @param  array<int,TKeywordEntry>  $entries
     */
    protected function keywordValuesToString(array $entries): ?string
    {
        if (empty($entries) || (count($entries) === 1 && ! $entries[0]['empty'])) {
            return null;
        }

        return ($entries[0]['empty'] ? '-' : '').implode('-', array_column($entries, 'value'));
    }

    /**
     * @return TKeywordsSet
     */
    protected function createKeywordSet(): array
    {
        return ['keywords' => [], 'prefix' => null, 'test' => null, 'partial' => null];
    }

    /**
     * @param  array<int,TKeywordEntry>  $entries
     * @param  TKeywordsSet  $set
     */
    protected function processKeywordEntries(array $entries, array &$set, bool $allowPartial, int $minPartial): void
    {
        $last = count($entries) - 1;

        foreach ($entries as $i => $entry) {
            if ($entry['empty']) {
                continue;
            }

            if ($i === $last && $allowPartial && strlen($entry['value']) >= $minPartial) {
                $set['partial'] ??= $entry['value'];
            } else {
                $set['keywords'][$entry['value']] = true;
            }
        }

        if ($testValue = $this->keywordValuesToString($entries)) {
            $set['test'][$testValue] = true;
        }
    }

    /**
     * @param  TKeywordResults|array<void>  $results
     * @param  TKeywordsSet  $set
     */
    protected function addKeywordResult(array &$results, array $set, ?string $prefix = null): void
    {
        $keywords = array_keys($set['keywords'] ?? []);
        $test = array_keys($set['test'] ?? []);
        $partial = $set['partial'] ?? null;

        if (! empty($keywords) || $partial) {
            $result = [
                'keywords' => array_filter($keywords, fn ($value) => ! empty($value)),
                'prefix' => $prefix,
                'partial' => $partial,
                'test' => ! empty($test) ? $test : null,
            ];

            $results[] = array_filter($result);
        }
    }
}
