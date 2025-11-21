<?php

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Illuminate\Support\Facades\Config;

it('prunes old logs based on retention_days', function () {
    Config::set('exception-logger.pruning.retention_days', 30);

    // Clear any existing logs first
    ExceptionLog::query()->delete();

    // Old log (31 days ago) - use fixed date for consistency
    $oldDate = now()->subDays(31)->toDateTimeString();
    ExceptionLog::create([
        'level' => 'ERROR',
        'message' => 'Old error',
        'context' => [],
        'created_at' => $oldDate,
        'updated_at' => $oldDate,
    ]);

    // Recent log (today) - use fixed date for consistency
    $recentDate = now()->toDateTimeString();
    ExceptionLog::create([
        'level' => 'ERROR',
        'message' => 'Recent error',
        'context' => [],
        'created_at' => $recentDate,
        'updated_at' => $recentDate,
    ]);

    expect(ExceptionLog::count())->toBe(2);

    // Run pruning query manually using the model's prunable method
    $exceptionLog = new ExceptionLog();
    $prunable = $exceptionLog->prunable();
    $deletedCount = $prunable->delete();

    // Verify exactly 1 record was deleted
    expect($deletedCount)->toBe(1);

    $logs = ExceptionLog::orderBy('created_at')->pluck('message')->all();

    expect($logs)->toBe(['Recent error']);
});
