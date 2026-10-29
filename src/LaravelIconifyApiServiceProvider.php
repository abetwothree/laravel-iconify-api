<?php

declare(strict_types=1);

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
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;
use Illuminate\Support\Facades\Blade;
use RuntimeException;
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
        $this->checkRemovedHelperConfig();

        $this->registerBladeDirective();

        if (config()->boolean('iconify-api.inline.enabled', true)) {
            $this->registerIconComponent();
        }

        $this->registerServicesBindings();
    }

    /**
     * The inline.helper config was removed in 1.6. Fail fast when an upgrader
     * relied on it to declare a renamed helper that nothing declares now.
     */
    protected function checkRemovedHelperConfig(): void
    {
        if (config()->get('iconify-api.inline.helper') === null) {
            return;
        }

        $name = config()->get('iconify-api.inline.helper.name');

        // v1 only declared a renamed helper with a valid PHP function name, while
        // inline rendering and the helper were both on.
        if (is_string($name)
            && $name !== 'icon'
            && preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*$/D', $name) === 1
            && ! function_exists($name)
            && config()->get('iconify-api.inline.enabled', true) !== false
            && config()->get('iconify-api.inline.helper.enabled', true) !== false) {
            throw new RuntimeException(
                "The iconify-api.inline.helper config was removed in 1.6, so {$name}() is no longer declared. "
                ."Declare {$name}() yourself or call icon(), then remove inline.helper from config/iconify-api.php. "
                .'See https://github.com/abetwothree/laravel-iconify-api#using-a-different-helper-name'
            );
        }

        trigger_error(
            'The iconify-api.inline.helper config was removed in 1.6 and is ignored; icon() is always declared. '
            .'Remove inline.helper from config/iconify-api.php. '
            .'See https://github.com/abetwothree/laravel-iconify-api#upgrading-to-16',
            E_USER_DEPRECATED
        );
    }

    /**
     * Register @iconify as a call that runs on every render.
     *
     * Building the tag here would freeze it into the compiled view, so a config change
     * would need view:clear and no request could get its own CSP nonce. The directive's
     * optional argument is that nonce, passed straight to IconifyDirective::render().
     */
    protected function registerBladeDirective(): void
    {
        Blade::directive('iconify', function (string $expression): string {
            return '<?php echo app(\\'.IconifyDirective::class.'::class)->render('.$expression.'); ?>';
        });
    }

    protected function registerServicesBindings(): void
    {
        // Iconify keeps its ID counter in module scope for the lifetime of the page
        // (packages/utils/src/svg/id.ts:23). A shared instance is what stops two
        // icons on the same response from emitting the same element ids.
        $this->app->singleton(SvgIdReplacer::class);
        $this->app->singleton(IconifySvgBuilder::class);

        $store = LaravelIconifyApi::cacheStore();

        $this->app->bind(IconSetsFileFinderContract::class, function ($app) use ($store) {
            if ($store !== '') {
                return resolve(IconSetsFileFinderCached::class);
            }

            return resolve(IconSetsFileFinder::class);
        });

        $this->app->bind(IconFinderContract::class, function ($app) use ($store) {
            if ($store !== '') {
                return resolve(IconFinderCached::class);
            }

            return resolve(IconFinder::class);
        });

        $this->app->bind(IconSetInfoSummaryFinderContract::class, function ($app) use ($store) {
            if ($store !== '') {
                return resolve(IconSetInfoSummaryFinderCached::class);
            }

            return resolve(IconSetInfoSummaryFinder::class);
        });

        $this->app->bind(IconSetInfoFinderContract::class, function ($app) use ($store) {
            if ($store !== '') {
                return resolve(IconSetInfoFinderCached::class);
            }

            return resolve(IconSetInfoFinder::class);
        });
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

        /** @var array<string, string> $aliases */
        $aliases = $bladeCompiler->getClassComponentAliases();

        return array_key_exists($name, $aliases);
    }
}
