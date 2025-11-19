<?php

// config for Arseno25/ExceptionLogger
return [
    'comments' => [
        'enabled' => (bool) env('EXCEPTION_LOGGER_COMMENTS_ENABLED', false),
    ],

    'pruning' => [
        'enabled' => true,
        'retention_days' => 30,
    ],

    'telegram' => [
        'enabled' => env('EXCEPTION_LOGGER_TELEGRAM_ENABLED', false),
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'throttle_minutes' => 5,
    ],

    'slack' => [
        'enabled' => env('EXCEPTION_LOGGER_SLACK_ENABLED', false),
        'webhook_url' => env('EXCEPTION_LOGGER_SLACK_WEBHOOK_URL'),
        'channel' => env('EXCEPTION_LOGGER_SLACK_CHANNEL', '#exceptions'),
        'username' => env('EXCEPTION_LOGGER_SLACK_USERNAME', 'Exception Logger'),
        'throttle_minutes' => 5,
    ],

    'discord' => [
        'enabled' => env('EXCEPTION_LOGGER_DISCORD_ENABLED', false),
        'webhook_url' => env('EXCEPTION_LOGGER_DISCORD_WEBHOOK_URL'),
        'username' => env('EXCEPTION_LOGGER_DISCORD_USERNAME', 'Exception Logger'),
        'throttle_minutes' => 5,
    ],

    'email' => [
        'enabled' => env('EXCEPTION_LOGGER_EMAIL_ENABLED', false),
        // Single email: 'admin@example.com'
        // Multiple emails (comma-separated): 'admin@example.com,dev@example.com'
        'to' => env('EXCEPTION_LOGGER_EMAIL_TO'),
        'from' => [
            'address' => env('EXCEPTION_LOGGER_EMAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
            'name' => env('EXCEPTION_LOGGER_EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Exception Logger')),
        ],
        'subject_prefix' => env('EXCEPTION_LOGGER_EMAIL_SUBJECT_PREFIX', '[Exception Alert]'),
        'throttle_minutes' => 5,
    ],

    'ai' => [
        'enabled' => (bool) env('EXCEPTION_LOGGER_AI_ENABLED', false),
        // Custom Base URL (Support OpenAI, Groq, DeepSeek, Ollama, etc)
        // Default: https://api.openai.com/v1
        'base_url' => env('EXCEPTION_LOGGER_AI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('EXCEPTION_LOGGER_AI_KEY'),
        'model' => env('EXCEPTION_LOGGER_AI_MODEL', 'gpt-3.5-turbo'),
        'temperature' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Critical Exception Detection
    |--------------------------------------------------------------------------
    |
    | Configuration to determine whether an exception is considered CRITICAL or not.
    | The system will automatically classify exceptions as CRITICAL if:
    | 1. Exception class is included in the critical exceptions list
    | 2. Exception message contains critical keywords
    | 3. HTTP status code >= 500 (for HTTP exceptions)
    |
    */

    'critical' => [
        // List of exception classes considered CRITICAL
        'exceptions' => [
            \Illuminate\Database\QueryException::class,
            \PDOException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
        ],

        // Keywords in exception message that indicate CRITICAL
        'keywords' => [
            'database',
            'connection',
            'timeout',
            'memory',
            'fatal',
            'segmentation',
            'out of memory',
        ],
    ],
];
