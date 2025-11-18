<?php

// config for Arseno25/ExceptionLogger
return [
    'pruning' => [
        'enabled' => true,
        'retention_days' => 30, // Hapus log lebih tua dari 30 hari
    ],

    'telegram' => [
        'enabled' => env('EXCEPTION_LOGGER_TELEGRAM_ENABLED', false),
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        // Mencegah spam: error yang sama tidak akan dikirim lagi dalam X menit
        'throttle_minutes' => 5,
    ],
];
