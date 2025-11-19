<?php

namespace Arseno25\ExceptionLogger\Models;

use Arseno25\ExceptionLogger\Enums\ExceptionLogStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $level
 * @property string $message
 * @property array|null $context
 * @property string|null $file
 * @property int|null $line
 * @property string|null $method
 * @property string|null $url
 * @property string|null $ip
 * @property string|null $user_agent
 * @property ExceptionLogStatus $status
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExceptionLogComment> $comments
 */
class ExceptionLog extends Model
{
    use Prunable;

    protected $table = 'exception_logger_table';

    protected $guarded = [];

    protected $casts = [
        'context' => 'array',
        'status' => ExceptionLogStatus::class,
    ];

    public function prunable(): Builder
    {
        $days = config('exception-logger.pruning.retention_days', 30);

        return static::where('created_at', '<=', now()->subDays($days));
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ExceptionLogComment::class, 'exception_log_id')
            ->latest();
    }

    /**
     * Get code snippet around the error line
     *
     * @param  int  $contextLines  Number of lines before and after the error line
     * @return array|null Returns array with 'lines', 'startLine', 'errorLine' or null if file doesn't exist
     */
    public function getSnippet(int $contextLines = 10): ?array
    {
        if (! $this->file || ! $this->line || ! file_exists($this->file)) {
            return null;
        }

        try {
            $file = new \SplFileObject($this->file);
            $errorLine = (int) $this->line;
            $startLine = max(1, $errorLine - $contextLines);
            $endLine = $errorLine + $contextLines;

            $lines = [];
            $currentLine = 1;

            // Read file line by line
            while (! $file->eof() && $currentLine <= $endLine) {
                $line = $file->current();

                if ($currentLine >= $startLine) {
                    $lines[$currentLine] = rtrim($line, "\r\n");
                }

                $file->next();
                $currentLine++;
            }

            return [
                'lines' => $lines,
                'startLine' => $startLine,
                'errorLine' => $errorLine,
                'file' => $this->file,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get file extension for syntax highlighting
     */
    public function getFileExtension(): ?string
    {
        if (! $this->file) {
            return null;
        }

        return pathinfo($this->file, PATHINFO_EXTENSION);
    }
}
