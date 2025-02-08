<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Commands\LaravelIconifyApiCommand;
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
            ->hasMigration('create_laravel_iconify_api_table')
            ->hasRoute('api')
            ->hasCommand(LaravelIconifyApiCommand::class);
    }
}
