<?php

namespace App\Console\Commands\Enrolments;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateRejectedApplicationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-rejected-applications-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update rejected applications status';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // select all students
        DB::table('student');
    }
}
