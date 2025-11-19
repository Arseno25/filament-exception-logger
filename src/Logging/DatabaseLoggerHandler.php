<?php

namespace Arseno25\ExceptionLogger\Logging;

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
        // ==================================================================
        // 1. IDEMPOTENCY CHECK (DUPLICATE PREVENTION)
        // ==================================================================

        // Create unique fingerprint from important error data
        $signatureData = [
            'msg' => $record->message,
            'lvl' => $record->level->name,
            'url' => Request::fullUrl(),
            'ip' => Request::ip(),
        ];

        // If exception object exists, add file & line for more specificity
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            $e = $record->context['exception'];
            $signatureData['file'] = $e->getFile();
            $signatureData['line'] = $e->getLine();
        }

        // Create unique hash
        $signature = 'log_lock_'.md5(json_encode($signatureData));

        // If exact same log already exists in cache (occurred < 2 seconds ago), STOP here.
        if (Cache::has($signature)) {
            return;
        }

        // Lock this signature for 2 seconds
        Cache::put($signature, true, now()->addSeconds(2));

        // ==================================================================
        // 2. PROCESS DATA & SAVE
        // ==================================================================

        $context = $record->context;
        $exception = $context['exception'] ?? null;

        unset($context['exception']);

        // Determine level based on exception or use level from record
        $determinedLevel = $this->determineLevel($exception, $record);

        $data = [
            'level' => $determinedLevel,
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
            // Silent fail to prevent main application from crashing if DB log has issues
        }

        // Send notifications to all enabled channels
        if (Config::get('exception-logger.telegram.enabled')) {
            $this->sendTelegram($record, $data);
        }

        if (Config::get('exception-logger.slack.enabled')) {
            $this->sendSlack($record, $data);
        }

        if (Config::get('exception-logger.discord.enabled')) {
            $this->sendDiscord($record, $data);
        }

        if (Config::get('exception-logger.email.enabled')) {
            $this->sendEmail($record, $data);
        }
    }

    protected function sendTelegram(LogRecord $record, array $data): void
    {
        $token = Config::get('exception-logger.telegram.token');
        $chatId = Config::get('exception-logger.telegram.chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        // Cache Key for Telegram is different (Throttling 5 minutes) to prevent notification spam
        // This logic is kept separate from Database Anti-Duplicate above.
        $cacheKey = 'telegram_log_'.md5($record->message);
        $throttleTime = Config::get('exception-logger.telegram.throttle_minutes', 5);

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
            // Silent fail
        }
    }

    protected function sendSlack(LogRecord $record, array $data): void
    {
        $webhookUrl = Config::get('exception-logger.slack.webhook_url');
        $channel = Config::get('exception-logger.slack.channel', '#exceptions');
        $username = Config::get('exception-logger.slack.username', 'Exception Logger');

        if (! $webhookUrl) {
            return;
        }

        // Cache Key for Slack (Throttling)
        $cacheKey = 'slack_log_'.md5($record->message);
        $throttleTime = Config::get('exception-logger.slack.throttle_minutes', 5);

        if (Cache::has($cacheKey)) {
            return;
        }

        $env = app()->environment();
        $level = $data['level'] ?? $record->level->name;
        $color = $this->getSlackColor($level);

        // Format Slack message with attachments
        $payload = [
            'channel' => $channel,
            'username' => $username,
            'attachments' => [
                [
                    'color' => $color,
                    'title' => '🚨 Exception Alert',
                    'fields' => [
                        [
                            'title' => 'Application',
                            'value' => config('app.name')." ({$env})",
                            'short' => true,
                        ],
                        [
                            'title' => 'Level',
                            'value' => $level,
                            'short' => true,
                        ],
                        [
                            'title' => 'URL',
                            'value' => $data['url'] ?? 'N/A',
                            'short' => false,
                        ],
                        [
                            'title' => 'IP Address',
                            'value' => $data['ip'] ?? 'N/A',
                            'short' => true,
                        ],
                        [
                            'title' => 'Method',
                            'value' => $data['method'] ?? 'N/A',
                            'short' => true,
                        ],
                        [
                            'title' => 'Message',
                            'value' => substr($record->message, 0, 1000),
                            'short' => false,
                        ],
                    ],
                    'ts' => time(),
                ],
            ],
        ];

        if (isset($data['file'])) {
            $payload['attachments'][0]['fields'][] = [
                'title' => 'Location',
                'value' => "{$data['file']}:{$data['line']}",
                'short' => false,
            ];
        }

        try {
            Http::timeout(3)->post($webhookUrl, $payload);
            Cache::put($cacheKey, true, now()->addMinutes($throttleTime));
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    protected function sendDiscord(LogRecord $record, array $data): void
    {
        $webhookUrl = Config::get('exception-logger.discord.webhook_url');
        $username = Config::get('exception-logger.discord.username', 'Exception Logger');

        if (! $webhookUrl) {
            return;
        }

        // Cache Key for Discord (Throttling)
        $cacheKey = 'discord_log_'.md5($record->message);
        $throttleTime = Config::get('exception-logger.discord.throttle_minutes', 5);

        if (Cache::has($cacheKey)) {
            return;
        }

        $env = app()->environment();
        $level = $data['level'] ?? $record->level->name;
        $color = $this->getDiscordColor($level);

        // Format Discord message with embed
        $payload = [
            'username' => $username,
            'embeds' => [
                [
                    'title' => '🚨 Exception Alert',
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Application',
                            'value' => config('app.name')." ({$env})",
                            'inline' => true,
                        ],
                        [
                            'name' => 'Level',
                            'value' => $level,
                            'inline' => true,
                        ],
                        [
                            'name' => 'URL',
                            'value' => substr($data['url'] ?? 'N/A', 0, 1024),
                            'inline' => false,
                        ],
                        [
                            'name' => 'IP Address',
                            'value' => $data['ip'] ?? 'N/A',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Method',
                            'value' => $data['method'] ?? 'N/A',
                            'inline' => true,
                        ],
                        [
                            'name' => 'Message',
                            'value' => substr($record->message, 0, 1000),
                            'inline' => false,
                        ],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ];

        if (isset($data['file'])) {
            $payload['embeds'][0]['fields'][] = [
                'name' => 'Location',
                'value' => "{$data['file']}:{$data['line']}",
                'inline' => false,
            ];
        }

        try {
            Http::timeout(3)->post($webhookUrl, $payload);
            Cache::put($cacheKey, true, now()->addMinutes($throttleTime));
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    protected function sendEmail(LogRecord $record, array $data): void
    {
        $to = Config::get('exception-logger.email.to');
        $fromAddress = Config::get('exception-logger.email.from.address');
        $fromName = Config::get('exception-logger.email.from.name', 'Exception Logger');
        $subjectPrefix = Config::get('exception-logger.email.subject_prefix', '[Exception Alert]');

        if (! $to || ! $fromAddress) {
            return;
        }

        // Cache Key for Email (Throttling)
        $cacheKey = 'email_log_'.md5($record->message);
        $throttleTime = Config::get('exception-logger.email.throttle_minutes', 5);

        if (Cache::has($cacheKey)) {
            return;
        }

        $env = app()->environment();
        $level = $data['level'] ?? $record->level->name;
        $subject = "{$subjectPrefix} {$level} - ".config('app.name')." ({$env})";

        try {
            $recipients = is_array($to) ? $to : explode(',', $to);
            Mail::raw($this->formatEmailContent($record, $data, $level, $env), function ($message) use ($recipients, $fromAddress, $fromName, $subject) {
                $message->to($recipients)
                    ->from($fromAddress, $fromName)
                    ->subject($subject);
            });

            Cache::put($cacheKey, true, now()->addMinutes($throttleTime));
        } catch (\Throwable $e) {
            // Silent fail
        }
    }

    /**
     * Format email content
     */
    protected function formatEmailContent(LogRecord $record, array $data, string $level, string $env): string
    {
        $content = "Exception Alert\n";
        $content .= str_repeat('=', 50)."\n\n";
        $content .= 'Application: '.config('app.name')." ({$env})\n";
        $content .= "Level: {$level}\n";
        $content .= 'Time: '.now()->format('Y-m-d H:i:s')."\n";
        $content .= 'URL: '.($data['url'] ?? 'N/A')."\n";
        $content .= 'Method: '.($data['method'] ?? 'N/A')."\n";
        $content .= 'IP Address: '.($data['ip'] ?? 'N/A')."\n";
        $content .= 'User Agent: '.($data['user_agent'] ?? 'N/A')."\n\n";

        if (isset($data['file'])) {
            $content .= "File: {$data['file']}\n";
            $content .= "Line: {$data['line']}\n\n";
        }

        $content .= "Message:\n";
        $content .= str_repeat('-', 50)."\n";
        $content .= $record->message."\n\n";

        if (! empty($data['context'])) {
            $content .= "Context:\n";
            $content .= str_repeat('-', 50)."\n";
            $content .= json_encode($data['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
        }

        return $content;
    }

    /**
     * Get color code for log level (for Slack)
     * Slack accepts hex colors without # or color names
     */
    protected function getSlackColor(string $level): string
    {
        return match (strtoupper($level)) {
            'EMERGENCY', 'ALERT', 'CRITICAL' => 'danger', // Red
            'ERROR' => 'FF0000', // Red
            'WARNING' => 'FFA500', // Orange
            'NOTICE' => '1E90FF', // Blue
            'INFO' => '32CD32', // Green
            'DEBUG' => '808080', // Gray
            default => '808080',
        };
    }

    /**
     * Get color code for log level (for Discord)
     * Discord accepts decimal color values
     */
    protected function getDiscordColor(string $level): int
    {
        $hexColor = match (strtoupper($level)) {
            'EMERGENCY', 'ALERT', 'CRITICAL' => 'FF0000', // Red
            'ERROR' => 'FF0000', // Red
            'WARNING' => 'FFA500', // Orange
            'NOTICE' => '1E90FF', // Blue
            'INFO' => '32CD32', // Green
            'DEBUG' => '808080', // Gray
            default => '808080',
        };

        return hexdec($hexColor);
    }

    /**
     * Determine log level based on exception type or specific criteria
     */
    protected function determineLevel(?\Throwable $exception, LogRecord $record): string
    {
        $currentLevel = $record->level->name;

        // Level order from lowest to highest
        $levelHierarchy = [
            'DEBUG' => 0,
            'INFO' => 1,
            'NOTICE' => 2,
            'WARNING' => 3,
            'ERROR' => 4,
            'CRITICAL' => 5,
            'ALERT' => 6,
            'EMERGENCY' => 7,
        ];

        $levelKey = strtoupper($currentLevel);
        $currentLevelValue = $levelHierarchy[$levelKey];

        // If exception exists, analyze to determine level
        if ($exception instanceof \Throwable) {
            $exceptionClass = get_class($exception);
            $message = strtolower($exception->getMessage());

            // Critical exceptions - very serious exceptions
            $criticalExceptions = Config::get('exception-logger.critical.exceptions', [
                \Illuminate\Database\QueryException::class,
                \PDOException::class,
                \Symfony\Component\HttpKernel\Exception\HttpException::class,
                \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
            ]);

            // Critical keywords in message
            $criticalKeywords = Config::get('exception-logger.critical.keywords', [
                'database',
                'connection',
                'timeout',
                'memory',
                'fatal',
                'segmentation',
                'out of memory',
            ]);

            $determinedLevel = null;

            // Check if exception class is critical
            foreach ($criticalExceptions as $criticalException) {
                if ($exception instanceof $criticalException || $exceptionClass === $criticalException) {
                    $determinedLevel = 'CRITICAL';
                    break;
                }
            }

            // Check if message contains critical keyword
            if (! $determinedLevel) {
                foreach ($criticalKeywords as $keyword) {
                    if (str_contains($message, strtolower($keyword))) {
                        $determinedLevel = 'CRITICAL';
                        break;
                    }
                }
            }

            // Check HTTP status code for HTTP exceptions
            if (! $determinedLevel && method_exists($exception, 'getStatusCode')) {
                $statusCode = $exception->getStatusCode();
                // 5xx errors are critical
                if ($statusCode >= 500) {
                    $determinedLevel = 'CRITICAL';
                }
            }

            // If level is determined, compare with current level
            // Use the higher level
            if ($determinedLevel) {
                $determinedLevelKey = strtoupper($determinedLevel);
                $determinedLevelValue = $levelHierarchy[$determinedLevelKey];

                return $determinedLevelValue > $currentLevelValue ? $determinedLevelKey : $levelKey;
            }
        }

        // Use level from record if no matching criteria
        return $levelKey;
    }
}
