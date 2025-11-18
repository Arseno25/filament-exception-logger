<?php

namespace Arseno25\ExceptionLogger;

use Arseno25\ExceptionLogger\Commands\ExceptionLoggerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExceptionLoggerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('exception-logger')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_exception_logger_table')
            ->hasCommand(ExceptionLoggerCommand::class);
    }
}
