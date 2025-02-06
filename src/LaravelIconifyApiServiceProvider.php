<?php

namespace AbeTwoThree\LaravelIconifyApi;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AbeTwoThree\LaravelIconifyApi\Commands\LaravelIconifyApiCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_iconify_api_table')
            ->hasCommand(LaravelIconifyApiCommand::class);
    }
}
