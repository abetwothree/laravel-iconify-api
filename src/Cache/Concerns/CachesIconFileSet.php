<?php

namespace AbeTwoThree\LaravelIconifyApi\Cache\Concerns;

use Illuminate\Support\Facades\Cache;

trait CachesIconFileSet
{
    public function getFileSet(string $prefix, string $type = 'icons'): ?string
    {
        $file = Cache::store($this->store)->get($this->fileSetKey($prefix, $type));

        if (! is_string($file)) {
            return null;
        }

        return $file;
    }

    public function setFileSet(string $prefix, string $file, string $type = 'icons'): void
    {
        Cache::store($this->store)->put($this->fileSetKey($prefix, $type), $file);
    }

    protected function fileSetKey(string $prefix, string $type): string
    {
        return "{$this->cachePrefix}:{$prefix}:{$type}";
    }
}
