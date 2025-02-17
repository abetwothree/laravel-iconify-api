<?php

namespace AbeTwoThree\LaravelIconifyApi\Tests;

use AbeTwoThree\LaravelIconifyApi\LaravelIconifyApiServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'AbeTwoThree\\LaravelIconifyApi\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelIconifyApiServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:yTtQNlEOB1IqYydLG9Z5pKRSxhZffdOxT1iuZIJi+eM=');
        config()->set('iconify-api.icons_location', __DIR__.'/../node_modules');
        config()->set('cache.default', 'file');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
