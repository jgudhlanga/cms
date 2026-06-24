<?php

namespace App\Models\Students;

use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\IdType;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApplicationFee extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'intake_period_id',
        'level_id',
        'id_type_id',
        'id_number',
        'passport_number',
        'status',
        'student_application_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationFeeStatusEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function intakePeriod(): BelongsTo
    {
        return $this->belongsTo(IntakePeriod::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function idType(): BelongsTo
    {
        return $this->belongsTo(IdType::class);
    }

    public function studentApplication(): BelongsTo
    {
        return $this->belongsTo(StudentApplication::class);
    }

    public function ledgerTransactions(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'ledgerable')->withTrashed();
    }

    public function hasPaidReceipt(): bool
    {
        return PaymentHelper::hasPaidReceipt($this, FeeTypeEnum::APPLICATION_FEE);
    }

    public function isPaid(): bool
    {
        return $this->status === ApplicationFeeStatusEnum::PAID
            || $this->status === ApplicationFeeStatusEnum::SUBMITTED;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === ApplicationFeeStatusEnum::AWAITING_PAYMENT;
    }
}
