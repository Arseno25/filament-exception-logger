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
        // Set up error handling before parent setup to prevent state issues
        $this->setupErrorHandler();

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Arseno25\\ExceptionLogger\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            // Always clean up error handlers, even if parent teardown fails
            $this->cleanupErrorHandlers();
        }
    }

    /**
     * Set up error handling to prevent state issues
     */
    protected function setupErrorHandler(): void
    {
        // Ensure we have clean error handler state
        if (function_exists('error_get_last')) {
            error_clear_last();
        }
    }

    /**
     * Clean up error handlers to prevent issues between tests
     */
    protected function cleanupErrorHandlers(): void
    {
        // Clean up any error handler state to prevent issues in CI
        $maxRestores = 10; // Prevent infinite loops
        $restoreCount = 0;

        while ($restoreCount < $maxRestores) {
            $restored = false;

            if (set_error_handler(function () {
                return false;
            }) !== null) {
                restore_error_handler();
                $restored = true;
            }

            if (set_exception_handler(function () {
                return false;
            }) !== null) {
                restore_exception_handler();
                $restored = true;
            }

            if (! $restored) {
                break;
            }

            $restoreCount++;
        }

        // Clear any final error handlers
        restore_error_handler();
        restore_exception_handler();
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
