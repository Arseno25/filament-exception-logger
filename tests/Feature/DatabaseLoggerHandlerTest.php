<?php

use Arseno25\ExceptionLogger\Logging\DatabaseLoggerHandler;
use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Monolog\Logger as MonologLogger;

it('stores an exception log in the database', function () {
    expect(ExceptionLog::count())->toBe(0);

    // Configure a logger that uses the package handler directly
    $monolog = new MonologLogger('test');
    $monolog->pushHandler(new DatabaseLoggerHandler);
    $logger = new Logger($monolog);

    $exception = new RuntimeException('Something went wrong');

    $logger->error('Test exception message', [
        'exception' => $exception,
    ]);

    expect(ExceptionLog::count())->toBe(1);

    $log = ExceptionLog::first();
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

    expect(ExceptionLog::count())->toBe(1);
});
