<?php

namespace Arseno25\ExceptionLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionLogComment extends Model
{
    protected $table = 'exception_log_comments';

    protected $fillable = [
        'content',
        'user_id',
    ];

    public function exceptionLog(): BelongsTo
    {
        return $this->belongsTo(ExceptionLog::class, 'exception_log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class));
    }
}

