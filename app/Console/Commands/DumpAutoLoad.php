<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Composer;

class DumpAutoLoad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dump-autoload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerates framework autoload files';

    /**
     * The Composer instance
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new Command instance
     *
     * @param Composer $composer
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->composer->dumpAutoloads();
    }
}
