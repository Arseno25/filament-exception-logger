<?php

namespace Arseno25\ExceptionLogger\Notifications;

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Arseno25\ExceptionLogger\Models\ExceptionLogComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExceptionLogMentioned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ExceptionLog $log,
        protected ExceptionLogComment $comment
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'exception-log-mentioned',
            'exception_log_id' => $this->log->id,
            'exception_log_message' => $this->log->message,
            'comment_id' => $this->comment->id,
            'comment_excerpt' => mb_strimwidth($this->comment->content, 0, 160, '...'),
            'mentioned_by' => optional($this->comment->user)->name,
            'mentioned_by_id' => $this->comment->user_id,
        ];
    }
}


