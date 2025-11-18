<?php

namespace Arseno25\ExceptionLogger\Resources\Pages;

use Arseno25\ExceptionLogger\Resources\ExceptionLogResource;
use Filament\Resources\Pages\ListRecords;

class ListExceptionLogs extends ListRecords
{
    protected static string $resource = ExceptionLogResource::class;
}
