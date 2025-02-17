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

    /**
     * @var TPrefixes
     */
    protected static array $prefixes = [];

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
     * @return TPrefixes
     */
    public function prefixes(): array
    {
        if (count(self::$prefixes) > 0) {
            return self::$prefixes;
        }

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
                if (in_array($prefix, $prefixes)) {
                    continue;
                }

                $prefixes[] = $prefix;
            }
        }

        sort($prefixes);

        self::$prefixes = $prefixes;

        return $prefixes;
    }
}
