<?php

namespace App\Console\Commands\Enrolments;

use App\Jobs\Enrolments\GenerateStudentNumberJob;
use Illuminate\Console\Command;

class GenerateStudentNumberCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-student-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate student numbers for verified students';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        GenerateStudentNumberJob::dispatch()->withoutDelay();
    }
}
