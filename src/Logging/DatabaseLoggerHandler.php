<?php

namespace Arseno25\ExceptionLogger\Logging;

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class DatabaseLoggerHandler extends AbstractProcessingHandler
{
    /**
     * {@inheritDoc}
     */
    protected function write(LogRecord $record): void
    {
        $context = $record->context;
        $exception = $context['exception'] ?? null;

        unset($context['exception']);

        $data = [
            'level' => $record->level->name,
            'message' => $record->message,
            'context' => $context,
            'method' => Request::method(),
            'url' => Request::fullUrl(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => Auth::id(),
        ];

        if ($exception instanceof \Throwable) {
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
        }

        try {
            ExceptionLog::create($data);
        } catch (\Throwable $e) {
            //
        }

        if (Config::get('exception-logger.telegram.enabled')) {
            $this->sendTelegram($record, $data);
        }
    }

    protected function sendTelegram(LogRecord $record, array $data): void
    {
        $token = Config::get('exception-logger.telegram.token');
        $chatId = Config::get('exception-logger.telegram.chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        $cacheKey = 'telegram_log_'.md5($record->message);
        $throttleTime = Config::get('exception-logger.telegram.throttle_minutes', 5);
        $cacheKey = 'telegram_log_'.md5($record->message);
        $throttleTime = Config::get('filament-exception-logger.telegram.throttle_minutes', 5);

        if (Cache::has($cacheKey)) {
            return;
        }

        $env = app()->environment();
        $msgPreview = substr($record->message, 0, 300);

        $text = "🚨 <b>EXCEPTION ALERT</b> 🚨\n\n";
        $text .= '<b>App:</b> '.config('app.name')." ({$env})\n";
        $text .= "<b>Level:</b> {$record->level->name}\n";
        $text .= "<b>URL:</b> {$data['url']}\n";
        $text .= "<b>IP:</b> {$data['ip']}\n\n";
        $text .= "<code>{$msgPreview}</code>";

        if (isset($data['file'])) {
            $text .= "\n\n📍 {$data['file']}:{$data['line']}";
        }

        try {
            Http::timeout(3)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            Cache::put($cacheKey, true, now()->addMinutes($throttleTime));

        } catch (\Throwable $e) {
            //
        }
    }
}
