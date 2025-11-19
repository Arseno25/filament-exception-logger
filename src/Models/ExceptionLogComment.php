<?php

namespace Arseno25\ExceptionLogger\Models;

use Arseno25\ExceptionLogger\Support\UserModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionLogComment extends Model
{
    protected $table = 'exception_log_comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'content',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'content' => 'string',
        'user_id' => 'integer',
    ];

    /**
     * The comment content.
     *
     * @var string
     */
    public $content;

    /**
     * The user ID who created the comment.
     *
     * @var int|null
     */
    public $user_id;

    public function exceptionLog(): BelongsTo
    {
        return $this->belongsTo(ExceptionLog::class, 'exception_log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModelResolver::resolve());
    }
}
