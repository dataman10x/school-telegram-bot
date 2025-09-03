<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Caches: cache, route, view, config';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('optimize:clear');
    }
}
