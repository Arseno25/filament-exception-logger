<?php

namespace Arseno25\ExceptionLogger\Commands;

use Illuminate\Console\Command;

class ExceptionLoggerCommand extends Command
{
    public $signature = 'exception-logger';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
