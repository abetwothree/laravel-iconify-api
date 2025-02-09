<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinderCached;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
            ->hasRoute('api');
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

        Blade::directive('iconify', function () {
            return (new IconifyDirective)->render();
        });
    }
}
