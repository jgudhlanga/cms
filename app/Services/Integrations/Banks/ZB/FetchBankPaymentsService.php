<?php

namespace App\Services\Integrations\Banks\ZB;

use App\Models\Integrations\Banks\BankPayment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class FetchBankPaymentsService
{
    /**
     * @param  callable(string):void|null  $info
     * @param  callable(string):void|null  $warn
     * @param  callable(string):void|null  $error
     */
    public function execute(string $rawAccountType, string $rawTransactionType, ?callable $info = null, ?callable $warn = null, ?callable $error = null): int
    {
        $accountType = $this->normalizeAccountType($rawAccountType, $warn);
        $transactionType = $this->normalizeTransactionType($rawTransactionType, $warn);

        $baseUrl = rtrim(trim((string) config('custom.payments.bank_payments_base_url')), '/');
        if ($baseUrl === '') {
            $this->emit($error, 'Missing config: custom.payments.bank_payments_base_url (BANK_PAYMENTS_BASE_URL).');

            return 1;
        }

        $credentials = $this->credentialsForAccountType($accountType);
        if ($credentials['institutionId'] === '' || $credentials['password'] === '') {
            $envAccountType = strtoupper(str_replace('-', '_', $accountType));
            $this->emit($error, "Missing bank credentials for accountType '{$accountType}'. Check BANK_PAYMENTS_{$envAccountType}_INSTITUTION_ID and BANK_PAYMENTS_{$envAccountType}_PASSWORD.");

            return 1;
        }

        $endpoint = $this->endpointForTransactionType($transactionType);
        $connectTimeout = max(1.0, (float) config('custom.payments.bank_payments_connect_timeout', 10));
        $timeout = max($connectTimeout, (float) config('custom.payments.bank_payments_timeout', 120));
        $retryTimes = max(1, (int) config('custom.payments.bank_payments_retry_times', 3));
        $retrySleepMilliseconds = max(0, (int) config('custom.payments.bank_payments_retry_sleep_ms', 250));
        $processingCount = 0;
        $successfulCount = 0;
        $failedCount = 0;

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->retry(
                    $retryTimes,
                    $retrySleepMilliseconds,
                    fn (Throwable $exception): bool => $exception instanceof ConnectionException
                )
                ->post($baseUrl.$endpoint, [
                    'institutionId' => $credentials['institutionId'],
                    'password' => $credentials['password'],
                ]);

            if (! $response->successful()) {
                $this->emit($error, "Bank payments API returned HTTP {$response->status()}.");
                $body = $response->body();
                if ($body !== '') {
                    $this->emit($info, $body);
                }

                return 1;
            }

            $data = $response->json();
            $payments = Arr::wrap(is_array($data) ? $data : ($data['payments'] ?? $data['data'] ?? []));

            collect($payments)->chunk(100)->each(function ($chunk) use (&$processingCount, &$successfulCount, &$failedCount, $info, $warn, $error): void {
                $rowsToUpsert = [];
                $timestamp = Carbon::now();

                foreach ($chunk as $payment) {
                    $payment = (array) $payment;
                    $transactionId = Arr::get($payment, 'transaction_id') ?? Arr::get($payment, 'transactionId') ?? Arr::get($payment, 'id', '');
                    if ((string) $transactionId === '') {
                        $this->emit($warn, 'Skipping payment with no transaction_id/id.');
                        $failedCount++;

                        continue;
                    }
                    $processingCount++;
                    $this->emit($info, "{$processingCount}. Processing payment: {$transactionId}");

                    $rowsToUpsert[] = [
                        'transaction_id' => (string) $transactionId,
                        ...$this->mapPaymentToAttributes($payment),
                        'updated_at' => $timestamp,
                        'created_at' => $timestamp,
                    ];
                }

                if ($rowsToUpsert === []) {
                    return;
                }

                try {
                    BankPayment::withoutEvents(function () use ($rowsToUpsert): void {
                        BankPayment::query()->upsert(
                            $rowsToUpsert,
                            ['transaction_id'],
                            [
                                'bank',
                                'amount',
                                'transaction_created_date',
                                'narrative',
                                'nr1',
                                'nr2',
                                'nr3',
                                'nr4',
                                'picked',
                                'reference',
                                'source',
                                'status',
                                'tcd',
                                'transaction_date',
                                'updated_at',
                            ]
                        );
                    });
                    $successfulCount += count($rowsToUpsert);
                } catch (Throwable $exception) {
                    $failedCount += count($rowsToUpsert);
                    $this->emit($error, 'Failed to persist payments chunk: '.$exception->getMessage());
                }
            });
        } catch (ConnectionException $exception) {
            $this->emit($error, "Connection error calling bank payments API: {$exception->getMessage()}");
            $this->emit($info, "HTTP client settings: connect_timeout={$connectTimeout}s timeout={$timeout}s retries={$retryTimes} retry_sleep_ms={$retrySleepMilliseconds}.");

            return 1;
        } catch (RequestException $exception) {
            $response = $exception->response;
            $this->emit($error, "Bank payments API returned HTTP {$response->status()}.");
            $body = $response->body();
            if ($body !== '') {
                $this->emit($info, $body);
            }

            return 1;
        }

        $this->emit($info, 'Totals - Processed: '.($successfulCount + $failedCount).', Successful: '.$successfulCount.', Failed: '.$failedCount.'.');
        $this->emit($info, 'Request completed successfully.');

        return 0;
    }

    private function emit(?callable $callback, string $message): void
    {
        if ($callback !== null) {
            $callback($message);
        }
    }

    private function normalizeAccountType(string $raw, ?callable $warn = null): string
    {
        $accountType = strtolower(trim($raw));
        if (in_array($accountType, ['usd', 'zwg', 'income-gen'], true)) {
            return $accountType;
        }

        $this->emit($warn, "Invalid accountType '{$raw}'. Defaulting to 'usd'. Allowed: usd, zwg, income-gen.");

        return 'usd';
    }

    private function normalizeTransactionType(string $raw, ?callable $warn = null): string
    {
        $transactionType = strtolower(trim($raw));
        if ($transactionType === 'all' || $transactionType === 'pending') {
            return $transactionType;
        }

        $this->emit($warn, "Invalid transactionType '{$raw}'. Defaulting to 'pending'. Allowed: all, pending.");

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
     * @param  array<string, mixed>  $payment
     * @return array<string, mixed>
     */
    private function mapPaymentToAttributes(array $payment): array
    {
        return [
            'bank' => Arr::get($payment, 'bank', 'zb'),
            'amount' => $this->normalizeAmount(Arr::get($payment, 'amount')),
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

    private function normalizeAmount(mixed $amount): ?string
    {
        if (! is_numeric($amount)) {
            return null;
        }

        $majorAmount = ((float) $amount) / 100;

        return number_format($majorAmount, 2, '.', '');
    }
}
