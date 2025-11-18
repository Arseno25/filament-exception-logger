<?php

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Illuminate\Support\Facades\Config;

it('prunes old logs based on retention_days', function () {
    Config::set('exception-logger.pruning.retention_days', 30);

    // Old log (31 days ago)
    ExceptionLog::create([
        'level' => 'ERROR',
        'message' => 'Old error',
        'context' => [],
        'created_at' => now()->subDays(31),
        'updated_at' => now()->subDays(31),
    ]);

    // Recent log (today)
    ExceptionLog::create([
        'level' => 'ERROR',
        'message' => 'Recent error',
        'context' => [],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(ExceptionLog::count())->toBe(2);

    // Run pruning query manually
    $prunable = (new ExceptionLog())->prunable();
    $prunable->delete();

    $logs = ExceptionLog::pluck('message')->all();

    expect($logs)->toBe(['Recent error']);
});
