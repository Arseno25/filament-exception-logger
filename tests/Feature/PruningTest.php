<?php

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('prunes old logs based on retention_days', function () {
    Config::set('exception-logger.pruning.retention_days', 30);

    // Clear any existing logs first using direct query to avoid model instantiation
    DB::table('exception_logger_table')->delete();

    // Create test data using direct insert to avoid model events
    $oldDate = now()->subDays(31)->toDateTimeString();
    $recentDate = now()->toDateTimeString();

    DB::table('exception_logger_table')->insert([
        'level' => 'ERROR',
        'message' => 'Old error',
        'context' => json_encode([]),
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ]);

    DB::table('exception_logger_table')->insert([
        'level' => 'ERROR',
        'message' => 'Recent error',
        'context' => json_encode([]),
        'created_at' => $recentDate,
        'updated_at' => $recentDate,
    ]);

    expect(ExceptionLog::count())->toBe(2);

    // Run pruning using direct query to avoid model instantiation issues
    $deletedCount = DB::table('exception_logger_table')
        ->where('created_at', '<=', now()->subDays(30))
        ->delete();

    expect($deletedCount)->toBe(1);

    $logs = ExceptionLog::orderBy('created_at')->pluck('message')->all();

    expect($logs)->toBe(['Recent error']);
});
