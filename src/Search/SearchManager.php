<?php

namespace AbeTwoThree\LaravelIconifyApi\Search;

use Illuminate\Config\Repository;
use Illuminate\Support\Manager;

class SearchManager extends Manager
{
    /**
     * The configuration repository instance.
     *
     * @var Repository
     */
    protected $config;

    public function getDefaultDriver(): string
    {
        return $this->config->string('iconify-api.search.default', 'files');
    }

    public function createFilesDriver(): SearchFilesDriver
    {
        /** @var SearchFilesDriver $driver */
        $driver = resolve(SearchFilesDriver::class);

        return $driver;
    }

    public function createDatabaseDriver(): SearchDatabaseDriver
    {
        /** @var SearchDatabaseDriver $driver */
        $driver = resolve(SearchDatabaseDriver::class);

        return $driver;
    }
}
