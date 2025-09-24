<?php

namespace App\Console\Commands\Users;

use Illuminate\Console\Command;

class CleanStudentUsersWithNoProfileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-student-users-with-no-profile-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up student users that do not have an associated profile and no payment records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up student users with no profile records started');
        $this->info('Done!');
    }
}
