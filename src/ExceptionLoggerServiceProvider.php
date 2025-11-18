<?php

namespace Arseno25\ExceptionLogger;

use Arseno25\ExceptionLogger\Commands\ExceptionLoggerCommand;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ExceptionLoggerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'exception-logger';
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */

        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_exception_logger_table')
            ->hasCommand(ExceptionLoggerCommand::class);
    }

    public function boot(): void
    {
        parent::boot();

        FilamentAsset::register([
            Css::make('exception-logger-ai-solution', __DIR__ . '/../resources/dist/ai-solution.css'),
        ], package: 'arseno25/exception-logger');
    }
}
