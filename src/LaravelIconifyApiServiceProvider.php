<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Commands\LaravelIconifyApiCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinderCached;
use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

class LaravelIconifyApiServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-iconify-api')
            ->hasConfigFile()
            ->hasMigration('create_laravel_iconify_api_table')
            ->hasRoute('api')
            ->hasCommand(LaravelIconifyApiCommand::class);
    }

    public function packageBooted(): void
    {
        $store = LaravelIconifyApi::cacheStore();

        $this->app->bind(IconSetsFileFinderContract::class, function ($app) use ($store) {
            if (! empty($store)) {
                return resolve(IconSetsFileFinderCached::class);
            }

            return resolve(IconSetsFileFinder::class);
        });

        $this->app->bind(IconFinderContract::class, function ($app) use ($store) {
            if (! empty($store)) {
                return resolve(IconFinderCached::class);
            }

            return resolve(IconFinder::class);
        });
    }
}
