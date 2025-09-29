<?php

namespace App\Console\Commands\Ledgers;

use App\Helpers\PaymentHelper;
use App\Models\Ledgers\Ledger;
use Illuminate\Console\Command;

class CleanLedgerEntriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-ledger-entries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up ledger entries in the database';

    public function handle(): void
    {
        $this->info('Cleaning up ledger entries...');

        Ledger::select('ledgerable_id', 'ledgerable_type')
            ->groupBy('ledgerable_id', 'ledgerable_type')
            ->orderBy('ledgerable_id')
            ->chunk(100, function ($groups) {
                foreach ($groups as $group) {
                    $entries = Ledger::where('ledgerable_id', $group->ledgerable_id)
                        ->where('ledgerable_type', $group->ledgerable_type)
                        ->get();

                    $paidInvoice = $entries->first(fn($e) => $e->type === 'invoice' && $e->payment_status === 'paid');
                    $paidReceipt = $entries->first(fn($e) => $e->type === 'receipt' && $e->payment_status === 'paid');

                    // If both exist, delete the rest
                    if ($paidInvoice && $paidReceipt) {
                        PaymentHelper::deleteNotPaidLedgerEntries($paidInvoice->system_reference);
                    }
                }
            });

        $this->info('Ledger entries cleanup completed.');

    }
}
