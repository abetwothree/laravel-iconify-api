<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Support;

class SvgIdReplacer
{
    /**
     * @var array<string, int>
     */
    protected array $counters = [];

    public function replace(string $body): string
    {
        preg_match_all('/\sid="(\S+)"/', $body, $matches);

        $ids = $matches[1];

        if (count($ids) === 0) {
            return $body;
        }

        $suffix = 'suffix'.dechex((random_int(0, 0x1000000) | time()));

        foreach ($ids as $id) {
            $newId = $this->nextId($id);
            $escapedId = preg_quote($id, '/');

            $body = (string) preg_replace(
                '/([#;"])(?:'.$escapedId.')([")]|\.[a-z])/',
                '$1'.$newId.$suffix.'$2',
                $body
            );
        }

        return str_replace($suffix, '', $body);
    }

    public function clear(): void
    {
        $this->counters = [];
    }

    protected function nextId(string $id): string
    {
        $id = preg_replace('/[0-9]+$/', '', $id) ?: 'a';

        $count = $this->counters[$id] ?? 0;
        $this->counters[$id] = $count + 1;

        return $count > 0 ? $id.$count : $id;
    }
}
