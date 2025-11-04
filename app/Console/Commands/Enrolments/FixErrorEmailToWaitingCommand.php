<?php

namespace App\Console\Commands\Enrolments;

use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use App\Models\Enrolments\ClassList;
use Illuminate\Console\Command;

class FixErrorEmailToWaitingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-to-waiting-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $classes = ClassList::where('type', 'waiting')->get();
        foreach ($classes as $class) {
            SendEnrolmentProgressJob::dispatch($class->id, 'waiting')->withoutDelay();
        }
    }
}
