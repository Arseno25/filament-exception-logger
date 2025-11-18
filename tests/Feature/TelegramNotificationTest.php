<?php

use Arseno25\ExceptionLogger\Logging\DatabaseLoggerHandler;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Monolog\Logger as MonologLogger;

it('does not send telegram notification when disabled', function () {
    Config::set('exception-logger.telegram.enabled', false);

    Http::fake(); // should not be called

    $monolog = new MonologLogger('test');
    $monolog->pushHandler(new DatabaseLoggerHandler);
    $logger = new Logger($monolog);

    $logger->error('Telegram disabled message', []);

    Http::assertNothingSent();
});

it('sends telegram notification when enabled and not throttled', function () {
    Config::set('exception-logger.telegram.enabled', true);
    Config::set('exception-logger.telegram.token', 'dummy-token');
    Config::set('exception-logger.telegram.chat_id', '123456');
    Config::set('exception-logger.telegram.throttle_minutes', 5);

    Cache::flush();

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $monolog = new MonologLogger('test');
    $monolog->pushHandler(new DatabaseLoggerHandler);
    $logger = new Logger($monolog);

    $logger->error('Telegram message', []);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'api.telegram.org')
            && $request['chat_id'] === '123456';
    });
});

it('throttles duplicate telegram notifications', function () {
    Config::set('exception-logger.telegram.enabled', true);
    Config::set('exception-logger.telegram.token', 'dummy-token');
    Config::set('exception-logger.telegram.chat_id', '123456');
    Config::set('exception-logger.telegram.throttle_minutes', 5);

    Cache::flush();

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $monolog = new MonologLogger('test');
    $monolog->pushHandler(new DatabaseLoggerHandler);
    $logger = new Logger($monolog);

    $logger->error('Same telegram message', []);
    $logger->error('Same telegram message', []);

    Http::assertSentCount(1);
});
