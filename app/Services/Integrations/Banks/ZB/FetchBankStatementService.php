<?php

namespace App\Services\Integrations\Banks\ZB;

use App\Models\Integrations\Banks\ZBBankStatement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class FetchBankStatementService
{
    /**
     * @param  callable(string):void|null  $info
     * @param  callable(string):void|null  $warn
     * @param  callable(string):void|null  $error
     */
    public function execute(string $rawAccountType, string $startDate, string $endDate, ?callable $info = null, ?callable $warn = null, ?callable $error = null): int
    {
        return $this->executeWithResult($rawAccountType, $startDate, $endDate, $info, $warn, $error)->exitCode;
    }

    /**
     * @param  callable(string):void|null  $info
     * @param  callable(string):void|null  $warn
     * @param  callable(string):void|null  $error
     */
    public function executeWithResult(string $rawAccountType, string $startDate, string $endDate, ?callable $info = null, ?callable $warn = null, ?callable $error = null): FetchBankStatementExecuteResult
    {
        $accountType = $this->normalizeAccountType($rawAccountType, $warn);

        $baseUrl = rtrim(trim((string) config('custom.bank-statements.base_url')), '/');
        if ($baseUrl === '') {
            $this->emit($error, 'Missing config: custom.bank-statements.base_url (BANK_STATEMENTS_BASE_URL).');

            return new FetchBankStatementExecuteResult(1, 0);
        }

        $credentials = $this->credentialsForAccountType($accountType);
        if ($credentials['accountNumber'] === '' || $credentials['password'] === '') {
            $envAccountType = strtoupper(str_replace('-', '_', $accountType));
            $this->emit($error, "Missing bank credentials for accountType '{$accountType}'. Check BANK_STATEMENTS_{$envAccountType}_ACCOUNT_NUMBER and BANK_STATEMENTS_{$envAccountType}_PASSWORD.");

            return new FetchBankStatementExecuteResult(1, 0);
        }

        $connectTimeout = max(1.0, (float) config('custom.bank-statements.connect_timeout', 10));
        $timeout = max($connectTimeout, (float) config('custom.bank-statements.timeout', 120));
        $retryTimes = max(1, (int) config('custom.bank-statements.retry_times', 3));
        $retrySleepMilliseconds = max(0, (int) config('custom.bank-statements.retry_sleep_ms', 250));
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
                ->post($baseUrl.'/v1/statement', [
                    'accountNumber' => $credentials['accountNumber'],
                    'password' => $credentials['password'],
                    'startDate' => Carbon::parse($startDate)->format('Y-m-d'),
                    'endDate' => Carbon::parse($endDate)->format('Y-m-d'),
                ]);

            if (! $response->successful()) {
                $status = $response->status();
                $this->emit($error, "Bank statement API returned HTTP {$status}.");
                $body = $response->body();
                if ($body !== '') {
                    $this->emit($info, $body);
                }

                return new FetchBankStatementExecuteResult(
                    1,
                    0,
                    resetWindowToPendingForRetry: $status === 401,
                );
            }

            $data = $response->json();
            $statements = Arr::wrap(Arr::get($data, 'transactions', Arr::get($data, 'data.transactions', Arr::get($data, 'data', []))));

            collect($statements)->chunk(100)->each(function ($chunk) use (&$processingCount, &$successfulCount, &$failedCount, $info, $warn, $error): void {
                $rowsToUpsert = [];
                $timestamp = Carbon::now();

                foreach ($chunk as $statement) {
                    $statement = (array) $statement;
                    $transactionId = Arr::get($statement, 'transactionId') ?? Arr::get($statement, 'transaction_id') ?? Arr::get($statement, 'id', '');
                    if ((string) $transactionId === '') {
                        $this->emit($warn, 'Skipping statement with no transactionId/transaction_id/id.');
                        $failedCount++;

                        continue;
                    }
                    $processingCount++;
                    $this->emit($info, "{$processingCount}. Processing statement: {$transactionId}");

                    $rowsToUpsert[] = [
                        'transaction_id' => (string) $transactionId,
                        ...$this->mapStatementAttributes($statement),
                        'updated_at' => $timestamp,
                        'created_at' => $timestamp,
                    ];
                }

                if ($rowsToUpsert === []) {
                    return;
                }

                try {
                    ZBBankStatement::withoutEvents(function () use ($rowsToUpsert): void {
                        ZBBankStatement::query()->upsert(
                            $rowsToUpsert,
                            ['transaction_id'],
                            [
                                'tran_number_asc',
                                'tran_number_desc',
                                'transaction_sr_id',
                                'transaction_date',
                                'narration',
                                'reference',
                                'code',
                                'description',
                                'debit_credit_flag',
                                'amount_credit',
                                'amount_debit',
                                'cleared_running_balance',
                                'blocked_balance',
                                'debit_limit',
                                'credit_limit',
                                'iso_currency_code',
                                'account_description',
                                'ubfull_name',
                                'pipe_count',
                                'pipe1',
                                'pipe2',
                                'pipe3',
                                'pipe4',
                                'pipe5',
                                'pipe6',
                                'pipe7',
                                'pipe8',
                                'pipe9',
                                'pipe10',
                                'pipe1_details',
                                'pipe2_details',
                                'pipe3_details',
                                'pipe4_details',
                                'pipe5_details',
                                'pipe6_details',
                                'pipe7_details',
                                'pipe8_details',
                                'pipe9_details',
                                'pipe10_details',
                                'transaction_details',
                                'updated_at',
                            ]
                        );
                    });
                    $successfulCount += count($rowsToUpsert);
                } catch (Throwable $exception) {
                    $failedCount += count($rowsToUpsert);
                    $this->emit($error, 'Failed to persist statements chunk: '.$exception->getMessage());
                }
            });
        } catch (ConnectionException $exception) {
            $this->emit($error, "Connection error calling bank statements API: {$exception->getMessage()}");
            $this->emit($info, "HTTP client settings: connect_timeout={$connectTimeout}s timeout={$timeout}s retries={$retryTimes} retry_sleep_ms={$retrySleepMilliseconds}.");

            return new FetchBankStatementExecuteResult(1, 0);
        } catch (RequestException $exception) {
            $response = $exception->response;
            $status = $response?->status() ?? 0;
            $this->emit($error, "Bank statements API returned HTTP {$status}.");
            $body = $response !== null ? $response->body() : '';
            if ($body !== '') {
                $this->emit($info, $body);
            }

            return new FetchBankStatementExecuteResult(
                1,
                0,
                resetWindowToPendingForRetry: $status === 401,
            );
        }

        $this->emit($info, 'Totals - Processed: '.($successfulCount + $failedCount).', Successful: '.$successfulCount.', Failed: '.$failedCount.'.');
        $this->emit($info, 'Request completed successfully.');

        return new FetchBankStatementExecuteResult(0, $failedCount);
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

    /**
     * @return array{accountNumber:string,password:string}
     */
    private function credentialsForAccountType(string $accountType): array
    {
        return [
            'accountNumber' => (string) config("custom.bank-statements.{$accountType}.account_number"),
            'password' => (string) config("custom.bank-statements.{$accountType}.password"),
        ];
    }

    /**
     * @param  array<string, mixed>  $statement
     * @return array<string, mixed>
     */
    private function mapStatementAttributes(array $statement): array
    {
        return [
            'tran_number_asc' => (string) Arr::get($statement, 'tranNumberAsc', ''),
            'tran_number_desc' => (string) Arr::get($statement, 'tranNumberDesc', ''),
            'transaction_sr_id' => (string) Arr::get($statement, 'transactionSRId', ''),
            'transaction_date' => (string) Arr::get($statement, 'transactionDate', ''),
            'narration' => Arr::get($statement, 'narration'),
            'reference' => Arr::get($statement, 'reference'),
            'code' => Arr::get($statement, 'code'),
            'description' => Arr::get($statement, 'description'),
            'debit_credit_flag' => Arr::get($statement, 'debitCreditFlag'),
            'amount_credit' => Arr::get($statement, 'amountCredit'),
            'amount_debit' => Arr::get($statement, 'amountDebit'),
            'cleared_running_balance' => Arr::get($statement, 'clearedRunningBalance'),
            'blocked_balance' => Arr::get($statement, 'blockedBalance'),
            'debit_limit' => Arr::get($statement, 'debitLimit'),
            'credit_limit' => Arr::get($statement, 'creditLimit'),
            'iso_currency_code' => Arr::get($statement, 'isoCurrencyCode'),
            'account_description' => Arr::get($statement, 'accountDescription'),
            'ubfull_name' => Arr::get($statement, 'ubfullName'),
            'pipe_count' => Arr::get($statement, 'pipeCount'),
            'pipe1' => Arr::get($statement, 'pipe1'),
            'pipe2' => Arr::get($statement, 'pipe2'),
            'pipe3' => Arr::get($statement, 'pipe3'),
            'pipe4' => Arr::get($statement, 'pipe4'),
            'pipe5' => Arr::get($statement, 'pipe5'),
            'pipe6' => Arr::get($statement, 'pipe6'),
            'pipe7' => Arr::get($statement, 'pipe7'),
            'pipe8' => Arr::get($statement, 'pipe8'),
            'pipe9' => Arr::get($statement, 'pipe9'),
            'pipe10' => Arr::get($statement, 'pipe10'),
            'pipe1_details' => Arr::get($statement, 'pipe1Details'),
            'pipe2_details' => Arr::get($statement, 'pipe2Details'),
            'pipe3_details' => Arr::get($statement, 'pipe3Details'),
            'pipe4_details' => Arr::get($statement, 'pipe4Details'),
            'pipe5_details' => Arr::get($statement, 'pipe5Details'),
            'pipe6_details' => Arr::get($statement, 'pipe6Details'),
            'pipe7_details' => Arr::get($statement, 'pipe7Details'),
            'pipe8_details' => Arr::get($statement, 'pipe8Details'),
            'pipe9_details' => Arr::get($statement, 'pipe9Details'),
            'pipe10_details' => Arr::get($statement, 'pipe10Details'),
            'transaction_details' => Arr::get($statement, 'transactionDetails'),
        ];
    }
}
