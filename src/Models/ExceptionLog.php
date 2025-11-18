<?php

namespace Arseno25\ExceptionLogger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ExceptionLog extends Model
{
    use Prunable;

    protected $table = 'exception_logger_table';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
    ];

    public function prunable(): Builder
    {
        $days = config('exception-logger.pruning.retention_days', 30);

        return static::where('created_at', '<=', now()->subDays($days));
    }
}
