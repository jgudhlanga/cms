<?php

namespace App\Console\Commands\Data;

use App\Helpers\Helper;
use App\Models\Institution\DocumentTemplate;
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
    public function handle(): void
    {
        $intakePeriod = Helper::resolveIntakePeriod();
        $documentTemplate = DocumentTemplate::find(1);
        $documentTemplate->update(['intake_period_id' => $intakePeriod->id]);
    }
}
