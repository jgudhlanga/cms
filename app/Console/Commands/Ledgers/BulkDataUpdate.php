<?php

namespace App\Console\Commands\Ledgers;

use App\Helpers\Helper;
use App\Models\Ledgers\Ledger;
use Illuminate\Console\Command;

class BulkDataUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:bulk-data-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk data update for ledgers';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Bulk data update started...');
        $intakePeriod = Helper::resolveIntakePeriod();

        // Count total ledgers for progress bar
        $total = Ledger::withTrashed()->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Ledger::withTrashed()->chunk(1000, function ($ledgers) use ($intakePeriod, $bar) {
            foreach ($ledgers as $ledger) {
                $wasTrashed = $ledger->trashed();

                // Restore if soft-deleted
                if ($wasTrashed) {
                    $ledger->restore();
                }

                // Update ledger fields
                $ledger->update([
                    'intake_period_id' => $intakePeriod?->id ?? null,
                    'payment_gateway' => 'smile-n-pay',
                ]);

                // Soft-delete again if it was originally trashed
                if ($wasTrashed) {
                    $ledger->delete();
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Bulk data update completed.');
    }

}
