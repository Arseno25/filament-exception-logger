<?php

namespace Arseno25\ExceptionLogger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Arseno25\ExceptionLogger\ExceptionLogger
 */
class ExceptionLogger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'exception-logger';
    }
}
