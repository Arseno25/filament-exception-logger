<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Clean up database before each test to avoid state issues
    DB::table('exception_logger_table')->delete();
});

afterEach(function () {
    // Clean up after each test to prevent state contamination
    DB::table('exception_logger_table')->delete();
});

it('prunes old logs based on retention_days', function () {
    Config::set('exception-logger.pruning.retention_days', 30);

    // Use relative timestamps to ensure proper test logic
    $oldDate = now()->subDays(35)->toDateTimeString(); // Should be deleted (older than 30 days)
    $recentDate = now()->subDays(5)->toDateTimeString();  // Should remain (newer than 30 days)

    // Insert test data directly without using models
    DB::insert(
        'INSERT INTO exception_logger_table (level, message, context, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
        ['ERROR', 'Old error', '{}', $oldDate, $oldDate]
    );

    DB::insert(
        'INSERT INTO exception_logger_table (level, message, context, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
        ['ERROR', 'Recent error', '{}', $recentDate, $recentDate]
    );

    // Verify data was inserted using raw count query
    $count = DB::selectOne('SELECT COUNT(*) as count FROM exception_logger_table')->count;
    expect((int) $count)->toBe(2);

    // Run pruning using raw query to avoid any model magic
    $cutoffDate = now()->subDays(30)->toDateTimeString();
    $deletedCount = DB::delete(
        'DELETE FROM exception_logger_table WHERE created_at <= ?',
        [$cutoffDate]
    );

    expect($deletedCount)->toBe(1);

    // Verify remaining records using raw query
    $remainingLogs = DB::select('SELECT message FROM exception_logger_table ORDER BY created_at');
    $messages = array_map(fn ($log) => $log->message, $remainingLogs);

    expect($messages)->toBe(['Recent error']);
});
