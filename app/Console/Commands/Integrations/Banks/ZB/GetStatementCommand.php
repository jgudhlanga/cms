<?php

namespace App\Console\Commands\Integrations\Banks\ZB;

use App\Services\Integrations\Banks\ZB\FetchBankStatementService;
use Illuminate\Console\Command;

class GetStatementCommand extends Command
{
    protected $signature = 'statements:get-request {accountType : usd|zwg|income-gen} {startDate : Y-m-d} {endDate : Y-m-d}';

    protected $description = 'Get statement fetch request from the bank for a given account type and date range';

    public function __construct(private readonly FetchBankStatementService $fetchBankStatementService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $exitCode = $this->fetchBankStatementService->execute(
            (string) $this->argument('accountType'),
            (string) $this->argument('startDate'),
            (string) $this->argument('endDate'),
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
