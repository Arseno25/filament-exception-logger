<?php

// config for Arseno25/ExceptionLogger
return [
    'comments' => [
        'enabled' => true,
    ],

    'pruning' => [
        'enabled' => true,
        'retention_days' => 30,
    ],

    'telegram' => [
        'enabled' => false,
        'token' => null,
        'chat_id' => null,
        'throttle_minutes' => 5,
    ],

    'slack' => [
        'enabled' => false,
        'webhook_url' => null,
        'channel' => '#exceptions',
        'username' => 'Exception Logger',
        'throttle_minutes' => 5,
    ],

    'discord' => [
        'enabled' => false,
        'webhook_url' => null,
        'username' => 'Exception Logger',
        'throttle_minutes' => 5,
    ],

    'email' => [
        'enabled' => false,
        // Single email: 'admin@example.com'
        // Multiple emails (comma-separated): 'admin@example.com,dev@example.com'
        'to' => null,
        'from' => [
            'address' => null,
            'name' => 'Exception Logger',
        ],
        'subject_prefix' => '[Exception Alert]',
        'throttle_minutes' => 5,
    ],

    'ai' => [
        'enabled' => false,
        // Custom Base URL (Support OpenAI, Groq, DeepSeek, Ollama, etc)
        // Default: https://api.openai.com/v1
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => null,
        'model' => 'gpt-3.5-turbo',
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
