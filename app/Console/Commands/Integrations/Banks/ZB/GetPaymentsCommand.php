<?php

namespace App\Console\Commands\Integrations\Banks\ZB;

use App\Services\Integrations\Banks\ZB\FetchBankPaymentsService;
use Illuminate\Console\Command;

class GetPaymentsCommand extends Command
{
    protected $signature = 'app:get-payments-command {accountType : usd|zwg|income-gen} {transactionType : all|pending}';

    protected $description = 'Get payments from the bank for a given account type and transaction type';

    public function __construct(private readonly FetchBankPaymentsService $fetchBankPaymentsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $exitCode = $this->fetchBankPaymentsService->execute(
            (string) $this->argument('accountType'),
            (string) $this->argument('transactionType'),
            info: function (string $message): void {
                $this->line($message);
            },
            warn: function (string $message): void {
                $this->warn($message);
            },
            error: function (string $message): void {
                $this->error($message);
            },
        );

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
