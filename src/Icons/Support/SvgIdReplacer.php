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

            // The braces are load-bearing: `'$1'.$newId` fuses into a higher-numbered
            // backreference whenever the new id starts with a digit (`$1` + `0abc`
            // reads as group 10, which does not exist), so the captured delimiter is
            // dropped and the output is invalid SVG. Upstream is immune because it
            // interpolates the id after the group reference rather than merging with
            // it: `"$1" + newID + suffix + "$3"`, node_modules/@iconify/utils/lib/svg/id.js.
            $body = (string) preg_replace(
                '/([#;"])(?:'.$escapedId.')([")]|\.[a-z])/',
                '${1}'.$newId.$suffix.'${2}',
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
