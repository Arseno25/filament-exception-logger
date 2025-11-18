<?php

// config for Arseno25/ExceptionLogger
return [
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

    'ai' => [
        'enabled' => (bool) env('EXCEPTION_LOGGER_AI_ENABLED', false),
        // Custom Base URL (Support OpenAI, Groq, DeepSeek, Ollama, etc)
        // Default: https://api.openai.com/v1
        'base_url' => env('EXCEPTION_LOGGER_AI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('EXCEPTION_LOGGER_AI_KEY'),
        'model' => env('EXCEPTION_LOGGER_AI_MODEL', 'gpt-3.5-turbo'),
        'temperature' => 0.5,
    ],
];
