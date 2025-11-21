<?php

namespace Arseno25\ExceptionLogger\Tests;

use Arseno25\ExceptionLogger\ExceptionLoggerServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Arseno25\\ExceptionLogger\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } catch (\Throwable $e) {
            // Ignore errors during teardown to prevent CI failures
            // This is a workaround for Orchestra Testbench error handler state issues
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            ExceptionLoggerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}
