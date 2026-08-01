<?php

namespace AbeTwoThree\LaravelIconifyApi;

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoSummaryFinder as IconSetInfoSummaryFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetInfoSummaryFinderCached;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\IconSetsFileFinderCached;
use AbeTwoThree\LaravelIconifyApi\Support\IconHelperRegistrar;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelIconifyApiServiceProvider extends PackageServiceProvider
{
    public function boot(): static
    {
        if (! config()->boolean('iconify-api.enabled')) {
            return $this;
        }

        /** @phpstan-ignore-next-line */
        return parent::boot();
    }

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
            ->hasRoute('api')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('abetwothree/laravel-iconify-api');
            });
    }

    public function packageBooted(): void
    {
        $this->registerBladeDirective();

        if (config()->boolean('iconify-api.inline.enabled', true)) {
            $this->registerIconHelper();
            $this->registerIconComponent();
        }

        $this->registerServicesBindings();
    }

    protected function registerBladeDirective(): void
    {
        Blade::directive('iconify', function () {
            return (new IconifyDirective)->render();
        });
    }

    protected function registerServicesBindings(): void
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

        $this->app->bind(IconSetInfoSummaryFinderContract::class, function ($app) use ($store) {
            if (! empty($store)) {
                return resolve(IconSetInfoSummaryFinderCached::class);
            }

            return resolve(IconSetInfoSummaryFinder::class);
        });

        $this->app->bind(IconSetInfoFinderContract::class, function ($app) use ($store) {
            if (! empty($store)) {
                return resolve(IconSetInfoFinderCached::class);
            }

            return resolve(IconSetInfoFinder::class);
        });
    }

    protected function registerIconHelper(): void
    {
        if (! config()->boolean('iconify-api.inline.helper.enabled', true)) {
            return;
        }

        $name = config()->string('iconify-api.inline.helper.name');

        if (! $this->isValidPhpFunctionName($name) || function_exists($name)) {
            return;
        }

        IconHelperRegistrar::register($name);
    }

    protected function registerIconComponent(): void
    {
        if (! config()->boolean('iconify-api.inline.component.enabled', true)) {
            return;
        }

        $name = config()->string('iconify-api.inline.component.name');

        if (blank($name) || $this->componentAliasExists($name)) {
            return;
        }

        Blade::component(Components\Icon::class, $name);
    }

    protected function componentAliasExists(string $name): bool
    {
        if (view()->exists('components.'.$name)) {
            return true;
        }

        if (view()->exists('components.'.str_replace('-', '.', $name))) {
            return true;
        }

        $bladeCompiler = app('blade.compiler');

        if (! method_exists($bladeCompiler, 'getClassComponentAliases')) {
            return false;
        }

        /** @var array<string, string> $aliases */
        $aliases = $bladeCompiler->getClassComponentAliases();

        return array_key_exists($name, $aliases);
    }

    protected function isValidPhpFunctionName(string $name): bool
    {
        if (blank($name)) {
            return false;
        }

        return Str::match('/^[A-Za-z_\\x80-\\xff][A-Za-z0-9_\\x80-\\xff]*$/', $name) !== '';
    }
}
