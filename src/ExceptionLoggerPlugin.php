<?php

namespace Arseno25\ExceptionLogger;

use Arseno25\ExceptionLogger\Resources\ExceptionLogResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

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
        ]);
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
