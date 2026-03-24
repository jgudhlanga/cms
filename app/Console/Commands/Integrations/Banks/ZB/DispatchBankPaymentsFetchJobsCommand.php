<?php

namespace App\Console\Commands\Integrations\Banks\ZB;

use App\Jobs\Integrations\Banks\ZB\FetchBankPaymentsJob;
use Illuminate\Console\Command;

class DispatchBankPaymentsFetchJobsCommand extends Command
{
    protected $signature = 'payments:dispatch';

    protected $description = 'Dispatch queued jobs to fetch bank payments for all configured account and transaction types';

    public function handle(): int
    {
        foreach ($this->dispatchMatrix() as [$accountType, $transactionType]) {
            FetchBankPaymentsJob::dispatch($accountType, $transactionType);
            $this->info("Dispatched payment fetch job for {$accountType}/{$transactionType}.");
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function dispatchMatrix(): array
    {
        return [
            ['usd', 'all'],
            ['usd', 'pending'],
            ['zwg', 'all'],
            ['zwg', 'pending'],
            ['income-gen', 'all'],
            ['income-gen', 'pending'],
        ];
    }
}
