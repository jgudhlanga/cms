<?php

namespace App\Jobs\Integrations\Banks\ZB;

use App\Services\Integrations\Banks\ZB\FetchBankPaymentsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchBankPaymentsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public string $accountType,
        public string $transactionType,
    ) {
        $this->onQueue((string) config('custom.payments.bank_payments_queue', 'payments'));
    }

    public function handle(FetchBankPaymentsService $fetchBankPaymentsService): void
    {
        $exitCode = $fetchBankPaymentsService->execute(
            $this->accountType,
            $this->transactionType,
            info: function (string $message): void {
                Log::info($message, [
                    'account_type' => $this->accountType,
                    'transaction_type' => $this->transactionType,
                ]);
            },
            warn: function (string $message): void {
                Log::warning($message, [
                    'account_type' => $this->accountType,
                    'transaction_type' => $this->transactionType,
                ]);
            },
            error: function (string $message): void {
                Log::error($message, [
                    'account_type' => $this->accountType,
                    'transaction_type' => $this->transactionType,
                ]);
            },
        );

        if ($exitCode !== 0) {
            throw new \RuntimeException("Failed fetching bank payments for {$this->accountType}/{$this->transactionType}.");
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'payments',
            "account:{$this->accountType}",
            "transaction:{$this->transactionType}",
        ];
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Payment fetch job failed.', [
            'account_type' => $this->accountType,
            'transaction_type' => $this->transactionType,
            'error' => $exception->getMessage(),
        ]);
    }
}
