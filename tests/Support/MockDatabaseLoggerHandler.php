<?php

namespace Arseno25\ExceptionLogger\Tests\Support;

use Arseno25\ExceptionLogger\Logging\DatabaseLoggerHandler;
use Illuminate\Support\Facades\Config;
use Monolog\LogRecord;

/**
 * Mock Database Logger Handler for testing
 * This handler disables all database operations to avoid error handler state issues
 */
class MockDatabaseLoggerHandler extends DatabaseLoggerHandler
{
    protected function write(LogRecord $record): void
    {
        // Store data for testing purposes without writing to database
        // This prevents error handler state issues from model instantiation

        // Only handle telegram notifications if enabled, skip database operations
        if (Config::get('exception-logger.telegram.enabled', false)) {
            parent::write($record); // Call parent for telegram logic only
        }

        // Database operations are skipped completely to avoid model instantiation
    }
}
