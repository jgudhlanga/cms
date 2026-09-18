<?php

declare(strict_types=1);

namespace App\Models\Integrations;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentGatewaySetting extends Model
{
    use LogsActivity;

    public const array SECRET_ATTRIBUTES = [
        'gateway_api_key',
        'gateway_secret',
        'usd_password',
        'zwg_password',
        'income_gen_password',
    ];

    public const array PUBLIC_ATTRIBUTES = [
        'gateway_name',
        'gateway_base_url',
        'bank_statements_base_url',
        'usd_account_number',
        'zwg_account_number',
        'income_gen_account_number',
    ];

    protected $fillable = [
        'gateway_name',
        'gateway_base_url',
        'gateway_api_key',
        'gateway_secret',
        'bank_statements_base_url',
        'usd_account_number',
        'usd_password',
        'zwg_account_number',
        'zwg_password',
        'income_gen_account_number',
        'income_gen_password',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateway_api_key' => 'encrypted',
            'gateway_secret' => 'encrypted',
            'usd_password' => 'encrypted',
            'zwg_password' => 'encrypted',
            'income_gen_password' => 'encrypted',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(self::SECRET_ATTRIBUTES)
            ->useLogName('PaymentGatewaySetting')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
