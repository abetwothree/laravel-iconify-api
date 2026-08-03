<?php

namespace AbeTwoThree\LaravelIconifyApi;

use Exception;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * @phpstan-type TPrefixes = array<int,string>
 */
class LaravelIconifyApi
{
    public static string $fullSetFolder = '@iconify';

    public static string $singleSetFolder = '@iconify-json';

    public function iconsLocation(): string
    {
        return config()->string('iconify-api.icons_location');
    }

    public function fullSetLocation(): string
    {
        return $this->iconsLocation().'/'.self::$fullSetFolder;
    }

    public function fullSetsJsonLocation(): string
    {
        return $this->fullSetLocation().'/json/json';
    }

    public function singleSetLocation(): string
    {
        return $this->iconsLocation().'/'.self::$singleSetFolder;
    }

    public function singleSetJsonLocation(string $prefix, string $type = 'icons'): string
    {
        $root = $this->singleSetLocation().'/'.$prefix.'/';

        return match ($type) {
            'icons' => $root.'icons.json',
            'info' => $root.'info.json',
            'metadata' => $root.'metadata.json',
            'chars' => $root.'chars.json',
            default => throw new Exception("Unknown icon set file type: {$type}"),
        };
    }

    public function cacheStore(): string
    {
        $store = config()->get('iconify-api.cache_store') ?? config()->get('cache.default');

        if (! is_string($store)) {
            throw new Exception('Cache store must be a string');
        }

        return $store;
    }

    public function domain(): ?string
    {
        $domain = config()->get('iconify-api.route_domain');

        if (! is_string($domain) && $domain !== null) {
            throw new Exception('Domain must be a string or null');
        }

        return $domain ? $domain : null;
    }

    public function path(): string
    {
        return Str::finish(config()->string('iconify-api.route_path'), '/').'api';
    }

    /**
     * Every icon set prefix installed under the icons location.
     *
     * Deliberately not memoised: a static memo outlives the container reset on Octane and
     * friends, freezing `/collections` at whatever the first worker request saw. Two
     * directory listings are cheap next to the JSON reads every caller goes on to do.
     *
     * @return TPrefixes
     */
    public function prefixes(): array
    {
        $prefixes = [];

        if (is_dir($this->singleSetLocation())) {
            $finder = new Finder;
            $finder->directories()->in($this->singleSetLocation());

            foreach ($finder as $folder) {
                $prefixes[] = $folder->getFilename();
            }
        }

        if (is_dir($this->fullSetsJsonLocation())) {
            $finder = new Finder;
            $finder->files()->in($this->fullSetsJsonLocation())->name('*.json');

            foreach ($finder as $file) {
                $prefix = $file->getBasename('.json');
                if (in_array($prefix, $prefixes, true)) {
                    continue;
                }

                $prefixes[] = $prefix;
            }
        }

        // SORT_STRING, because SORT_REGULAR compares two numeric-looking prefixes as
        // numbers — and `1e2`, `100` and `9` are all names matchIconName accepts.
        sort($prefixes, SORT_STRING);

        return $prefixes;
    }
}
