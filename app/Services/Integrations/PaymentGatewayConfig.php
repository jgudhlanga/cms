<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\Integrations\PaymentGatewaySetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class PaymentGatewayConfig
{
    public const string CACHE_KEY = 'payment_gateway_config';

    public const int CACHE_TTL_SECONDS = 3600;

    public const string UNLOCK_SESSION_KEY = 'payment_gateway_unlocked_at';

    public const int UNLOCK_TTL_SECONDS = 300;

    /**
     * @var array<string, array{value: string, source: 'database'|'env'|'missing'}>|null
     */
    private static ?array $memo = null;

    /**
     * @return array<string, array{value: string, source: 'database'|'env'|'missing'}>
     */
    public function resolved(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        /** @var array<string, array{value: string, source: 'database'|'env'|'missing'}> $resolved */
        $resolved = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn (): array => $this->merge(),
        );

        self::$memo = $resolved;

        return $resolved;
    }

    public function value(string $key): string
    {
        return $this->resolved()[$key]['value'] ?? '';
    }

    public function applyToRuntimeConfig(): void
    {
        $resolved = $this->resolved();

        config([
            'custom.payments.payment-gateway.name' => $resolved['gateway_name']['value'],
            'custom.payments.payment-gateway.base_url' => $resolved['gateway_base_url']['value'],
            'custom.payments.payment-gateway.api_key' => $resolved['gateway_api_key']['value'],
            'custom.payments.payment-gateway.secret' => $resolved['gateway_secret']['value'],
            'custom.bank-statements.base_url' => $resolved['bank_statements_base_url']['value'],
            'custom.bank-statements.usd.account_number' => $resolved['usd_account_number']['value'],
            'custom.bank-statements.usd.password' => $resolved['usd_password']['value'],
            'custom.bank-statements.zwg.account_number' => $resolved['zwg_account_number']['value'],
            'custom.bank-statements.zwg.password' => $resolved['zwg_password']['value'],
            'custom.bank-statements.income-gen.account_number' => $resolved['income_gen_account_number']['value'],
            'custom.bank-statements.income-gen.password' => $resolved['income_gen_password']['value'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function forDisplay(): array
    {
        $resolved = $this->resolved();
        $display = [];

        foreach ($resolved as $key => $field) {
            if (in_array($key, PaymentGatewaySetting::SECRET_ATTRIBUTES, true)) {
                $display[$key] = [
                    'isSet' => $field['source'] !== 'missing',
                    'source' => $field['source'],
                ];

                continue;
            }

            $display[$key] = [
                'value' => $field['value'],
                'source' => $field['source'],
            ];
        }

        $updatedAt = PaymentGatewaySetting::query()->value('updated_at');
        $display['updated_at'] = $updatedAt === null
            ? null
            : Carbon::parse($updatedAt)
                ->timezone((string) config('app.timezone'))
                ->toIso8601String();

        return $display;
    }

    public function forget(): void
    {
        self::flushMemo();
        Cache::forget(self::CACHE_KEY);
    }

    public static function flushMemo(): void
    {
        self::$memo = null;
    }

    /**
     * @return array<string, array{value: string, source: 'database'|'env'|'missing'}>
     */
    private function merge(): array
    {
        $row = PaymentGatewaySetting::query()->first();

        return [
            'gateway_name' => $this->field($row?->gateway_name, config('custom.payments.payment-gateway.name')),
            'gateway_base_url' => $this->field($row?->gateway_base_url, config('custom.payments.payment-gateway.base_url')),
            'gateway_api_key' => $this->field($row?->gateway_api_key, config('custom.payments.payment-gateway.api_key')),
            'gateway_secret' => $this->field($row?->gateway_secret, config('custom.payments.payment-gateway.secret')),
            'bank_statements_base_url' => $this->field($row?->bank_statements_base_url, config('custom.bank-statements.base_url')),
            'usd_account_number' => $this->field($row?->usd_account_number, config('custom.bank-statements.usd.account_number')),
            'usd_password' => $this->field($row?->usd_password, config('custom.bank-statements.usd.password')),
            'zwg_account_number' => $this->field($row?->zwg_account_number, config('custom.bank-statements.zwg.account_number')),
            'zwg_password' => $this->field($row?->zwg_password, config('custom.bank-statements.zwg.password')),
            'income_gen_account_number' => $this->field($row?->income_gen_account_number, config('custom.bank-statements.income-gen.account_number')),
            'income_gen_password' => $this->field($row?->income_gen_password, config('custom.bank-statements.income-gen.password')),
        ];
    }

    /**
     * @return array{value: string, source: 'database'|'env'|'missing'}
     */
    private function field(mixed $database, mixed $fallback): array
    {
        $databaseValue = $this->trimmed($database);

        if ($databaseValue !== '') {
            return ['value' => $databaseValue, 'source' => 'database'];
        }

        $envValue = $this->trimmed($fallback);

        if ($envValue !== '') {
            return ['value' => $envValue, 'source' => 'env'];
        }

        return ['value' => '', 'source' => 'missing'];
    }

    private function trimmed(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
