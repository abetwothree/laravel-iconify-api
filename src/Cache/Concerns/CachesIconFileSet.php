<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache\Concerns;

use Illuminate\Support\Facades\Cache;

trait CachesIconFileSet
{
    public function getFileSet(string $set): ?string
    {
        $file = Cache::store($this->store)->get($this->fileSetKey($set));

        if (! is_string($file)) {
            return null;
        }

        return $file;
    }

    public function setFileSet(string $set, string $file): void
    {
        Cache::store($this->store)->put($this->fileSetKey($set), $file);
    }

    protected function fileSetKey(string $set): string
    {
        return "{$this->cachePrefix}:{$set}:file";
    }
}
