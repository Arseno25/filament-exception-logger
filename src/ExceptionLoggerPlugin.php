<?php

namespace Arseno25\ExceptionLogger;

use Arseno25\ExceptionLogger\Resources\ExceptionLogResource;
use Arseno25\ExceptionLogger\Widgets\ExceptionStatsOverview;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Assets\Css;

class ExceptionLoggerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'exception-logger';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ExceptionLogResource::class,
            ])
            ->widgets([
                ExceptionStatsOverview::class,
            ])
            ->assets([
                Css::make('exception-logger-ai-solution', __DIR__.'/../resources/dist/ai-solution.css'),
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
