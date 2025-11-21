<?php

use Arseno25\ExceptionLogger\Logging\DatabaseLoggerHandler;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Monolog\Logger as MonologLogger;

beforeEach(function () {
    // Disable all notifications to avoid external calls during testing
    Config::set('exception-logger.telegram.enabled', false);
    Config::set('exception-logger.slack.enabled', false);
    Config::set('exception-logger.discord.enabled', false);
    Config::set('exception-logger.email.enabled', false);

    // Clean up database before each test
    DB::table('exception_logger_table')->delete();
});

afterEach(function () {
    // Clean up after each test
    DB::table('exception_logger_table')->delete();
});

it('stores an exception log in the database', function () {
    // Use raw count query to avoid model instantiation
    $count = DB::selectOne('SELECT COUNT(*) as count FROM exception_logger_table')->count;
    expect((int) $count)->toBe(0);

    // Configure a logger that uses the package handler directly
    $monolog = new MonologLogger('test');
    $monolog->pushHandler(new DatabaseLoggerHandler);
    $logger = new Logger($monolog);

    $exception = new RuntimeException('Something went wrong');

    $logger->error('Test exception message', [
        'exception' => $exception,
    ]);

    // Use raw count query to avoid model instantiation
    $count = DB::selectOne('SELECT COUNT(*) as count FROM exception_logger_table')->count;
    expect((int) $count)->toBe(1);

    // Use raw query to verify data was stored correctly
    $log = DB::selectOne('SELECT * FROM exception_logger_table LIMIT 1');

    expect($log->message)->toBe('Test exception message')
        ->and($log->level)->toBe('Error')
        ->and($log->file)->toBe($exception->getFile())
        ->and($log->line)->toBe($exception->getLine());
});

it('avoids storing duplicate logs in a short time window', function () {
    Cache::flush();

    $monolog = new MonologLogger('test');
    $monolog->pushHandler(new DatabaseLoggerHandler);
    $logger = new Logger($monolog);

    $logger->error('Duplicated message', []);
    $logger->error('Duplicated message', []); // should be skipped by idempotency check

    // Use raw count query to avoid model instantiation
    $count = DB::selectOne('SELECT COUNT(*) as count FROM exception_logger_table')->count;
    expect((int) $count)->toBe(1);
});
