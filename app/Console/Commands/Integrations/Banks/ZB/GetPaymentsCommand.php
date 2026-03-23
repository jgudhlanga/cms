<?php

namespace App\Console\Commands\Integrations\Banks\ZB;

use App\Models\Integrations\Banks\BankPayment;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GetPaymentsCommand extends Command
{
    protected $signature = 'app:get-payments-command {accountType : usd|zwg|income-gen} {transactionType : all|pending}';

    protected $description = 'Get payments from the bank for a given account type and transaction type';

    public function handle(): int
    {
        $accountType = $this->normalizeAccountType((string) $this->argument('accountType'));
        $transactionType = $this->normalizeTransactionType((string) $this->argument('transactionType'));

        $baseUrl = rtrim(trim((string) config('custom.payments.bank_payments_base_url')), '/');
        if ($baseUrl === '') {
            $this->error('Missing config: custom.payments.bank_payments_base_url (BANK_PAYMENTS_BASE_URL).');

            return self::FAILURE;
        }

        $credentials = $this->credentialsForAccountType($accountType);
        if ($credentials['institutionId'] === '' || $credentials['password'] === '') {
            $envAccountType = strtoupper(str_replace('-', '_', $accountType));
            $this->error("Missing bank credentials for accountType '{$accountType}'. Check BANK_PAYMENTS_{$envAccountType}_INSTITUTION_ID and BANK_PAYMENTS_{$envAccountType}_PASSWORD.");

            return self::FAILURE;
        }

        $endpoint = $this->endpointForTransactionType($transactionType);
        $processingCount = 0;
        $successfulCount = 0;
        $failedCount = 0;

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->post($baseUrl.$endpoint, [
                    'institutionId' => $credentials['institutionId'],
                    'password' => $credentials['password'],
                ]);

            if (! $response->successful()) {
                $this->error("Bank payments API returned HTTP {$response->status()}.");
                $body = $response->body();
                if ($body !== '') {
                    $this->line($body);
                }

                return self::FAILURE;
            }

            $data = $response->json();
            $payments = Arr::wrap(is_array($data) ? $data : ($data['payments'] ?? $data['data'] ?? []));

            collect($payments)->chunk(100)->each(function ($chunk) use (&$processingCount, &$successfulCount, &$failedCount): void {
                foreach ($chunk as $payment) {
                    $payment = (array) $payment;
                    $transactionId = Arr::get($payment, 'transaction_id') ?? Arr::get($payment, 'transactionId') ?? Arr::get($payment, 'id', '');
                    if ((string) $transactionId === '') {
                        $this->warn('Skipping payment with no transaction_id/id.');
                        $failedCount++;

                        continue;
                    }
                    $processingCount++;
                    $this->info("{$processingCount}. Processing payment: {$transactionId}");

                    try {
                        BankPayment::updateOrCreate(
                            ['transaction_id' => (string) $transactionId],
                            $this->mapPaymentToAttributes($payment)
                        );
                        $successfulCount++;
                    } catch (\Throwable $exception) {
                        $failedCount++;
                        $this->error("Failed to persist payment {$transactionId}: {$exception->getMessage()}");
                    }
                }
            });
        } catch (ConnectionException $e) {
            $this->error("Connection error calling bank payments API: {$e->getMessage()}");

            return self::FAILURE;
        } catch (RequestException $e) {
            $response = $e->response;
            $this->error("Bank payments API returned HTTP {$response->status()}.");
            $body = $response->body();
            if ($body !== '') {
                $this->line($body);
            }

            return self::FAILURE;
        }

        $this->info('Totals - Processed: '.($successfulCount + $failedCount).', Successful: '.$successfulCount.', Failed: '.$failedCount.'.');
        $this->info('Request completed successfully.');

        return self::SUCCESS;
    }

    private function normalizeAccountType(string $raw): string
    {
        $accountType = strtolower(trim($raw));
        if (in_array($accountType, ['usd', 'zwg', 'income-gen'], true)) {
            return $accountType;
        }

        $this->warn("Invalid accountType '{$raw}'. Defaulting to 'usd'. Allowed: usd, zwg, income-gen.");

        return 'usd';
    }

    private function normalizeTransactionType(string $raw): string
    {
        $transactionType = strtolower(trim($raw));
        if ($transactionType === 'all' || $transactionType === 'pending') {
            return $transactionType;
        }

        $this->warn("Invalid transactionType '{$raw}'. Defaulting to 'pending'. Allowed: all, pending.");

        return 'pending';
    }

    private function endpointForTransactionType(string $transactionType): string
    {
        return $transactionType === 'all'
            ? '/alerts/payments/all-payments'
            : '/alerts/payments/pick-all-pending';
    }

    /**
     * @return array{institutionId:string,password:string}
     */
    private function credentialsForAccountType(string $accountType): array
    {
        return [
            'institutionId' => (string) config("custom.payments.{$accountType}.institution_id"),
            'password' => (string) config("custom.payments.{$accountType}.password"),
        ];
    }

    /**
     * Map API payment payload to BankPayment fillable attributes.
     * Adjust keys if your bank API uses different field names.
     *
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>
     */
    private function mapPaymentToAttributes(array $payment): array
    {
        return [
            'bank' => Arr::get($payment, 'bank', 'zb'),
            'amount' => Arr::get($payment, 'amount'),
            'transaction_created_date' => Arr::get($payment, 'transaction_created_datedate') ?? Arr::get($payment, 'date'),
            'narrative' => Arr::get($payment, 'narrative'),
            'nr1' => Arr::get($payment, 'nr1'),
            'nr2' => Arr::get($payment, 'nr2'),
            'nr3' => Arr::get($payment, 'nr3'),
            'nr4' => Arr::get($payment, 'nr4'),
            'picked' => Arr::get($payment, 'picked'),
            'reference' => Arr::get($payment, 'reference'),
            'source' => Arr::get($payment, 'source'),
            'status' => Arr::get($payment, 'status'),
            'tcd' => Arr::get($payment, 'tcd'),
            'transaction_date' => Arr::get($payment, 'transaction_date') ?? Arr::get($payment, 'transactionDate'),
        ];
    }
}
