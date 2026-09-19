<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;

class FixDataIssuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-data-issues-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix data issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('No pending data fixes.');

        return self::SUCCESS;
    }
}
