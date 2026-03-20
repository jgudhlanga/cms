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

    protected $signature = 'app:get-payments-command {currency : usd|zwg|income-gen} {type : all|pending}';


    protected $description = 'Get payments from the bank for a given currency and type';


    public function handle(): int
    {
        $currency = $this->normalizeCurrency((string) $this->argument('currency'));
        $type = $this->normalizeType((string) $this->argument('type'));

        $baseUrl = rtrim(trim((string) config('custom.payments.bank_payments_base_url')), '/');
        if ($baseUrl === '') {
            $this->error('Missing config: custom.payments.bank_payments_base_url (BANK_PAYMENTS_BASE_URL).');
            return self::FAILURE;
        }

        $credentials = $this->credentialsForCurrency($currency);
        if ($credentials['institutionId'] === '' || $credentials['password'] === '') {
            $envCurrency = strtoupper($currency);
            $this->error("Missing bank credentials for currency '{$currency}'. Check BANK_PAYMENTS_{$envCurrency}_INSTITUTION_ID and BANK_PAYMENTS_{$envCurrency}_PASSWORD.");
            return self::FAILURE;
        }

        $endpoint = $this->endpointForType($type);

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->post($baseUrl . $endpoint, [
                    'institutionId' => $credentials['institutionId'],
                    'password' => $credentials['password'],
                ]);

            if (!$response->successful()) {
                $this->error("Bank payments API returned HTTP {$response->status()}.");
                $body = $response->body();
                if ($body !== '') {
                    $this->line($body);
                }
                return self::FAILURE;
            }

            $data = $response->json();
            $payments = Arr::wrap(is_array($data) ? $data : ($data['payments'] ?? $data['data'] ?? []));

            collect($payments)->chunk(100)->each(function ($chunk) {
                foreach ($chunk as $payment) {
                    $payment = (array) $payment;
                    $transactionId = Arr::get($payment, 'transaction_id') ?? Arr::get($payment, 'transactionId') ?? Arr::get($payment, 'id', '');
                    if ((string) $transactionId === '') {
                        $this->warn('Skipping payment with no transaction_id/id.');
                        continue;
                    }
                    $this->info('Processing payment: ' . $transactionId);
                    BankPayment::updateOrCreate(
                        ['transaction_id' => (string) $transactionId],
                        $this->mapPaymentToAttributes($payment)
                    );
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

        $this->info('Request completed successfully.');
        return self::SUCCESS;
    }

    private function normalizeCurrency(string $raw): string
    {
        $currency = strtolower(trim($raw));
        if ($currency === 'usd' || $currency === 'zwg') {
            return $currency;
        }

        $this->warn("Invalid currency '{$raw}'. Defaulting to 'usd'. Allowed: usd, zwg.");
        return 'usd';
    }

    private function normalizeType(string $raw): string
    {
        $type = strtolower(trim($raw));
        if ($type === 'all' || $type === 'pending') {
            return $type;
        }

        $this->warn("Invalid type '{$raw}'. Defaulting to 'pending'. Allowed: all, pending.");
        return 'pending';
    }

    private function endpointForType(string $type): string
    {
        return $type === 'all'
            ? '/alerts/payments/all-payments'
            : '/alerts/payments/pick-all-pending';
    }

    /**
     * @return array{institutionId:string,password:string}
     */
    private function credentialsForCurrency(string $currency): array
    {
        return [
            'institutionId' => (string) config("custom.payments.{$currency}.institution_id"),
            'password' => (string) config("custom.payments.{$currency}.password"),
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
