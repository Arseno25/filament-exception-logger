<?php

namespace Arseno25\ExceptionLogger\Tests;

use Arseno25\ExceptionLogger\ExceptionLoggerServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
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
        parent::tearDown();

        // Clean up any error handler state to prevent issues in CI
        if (class_exists(\PHPUnit\Runner\ErrorHandler::class)) {
            // Restore error handlers to prevent state issues between tests
            restore_error_handler();
            restore_exception_handler();
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
