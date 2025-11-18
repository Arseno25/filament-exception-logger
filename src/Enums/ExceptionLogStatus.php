<?php

namespace Arseno25\ExceptionLogger\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExceptionLogStatus: string implements HasLabel, HasColor
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Ignored = 'ignored';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Ignored => 'Ignored',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::New => 'danger',
            self::InProgress => 'warning',
            self::Resolved => 'success',
            self::Ignored => 'gray',
        };
    }
}
