<?php

namespace AbeTwoThree\LaravelIconifyApi\Commands;

use Illuminate\Console\Command;

class LaravelIconifyApiCommand extends Command
{
    public $signature = 'laravel-iconify-api';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
