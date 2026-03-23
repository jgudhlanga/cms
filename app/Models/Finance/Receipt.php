<?php

namespace App\Models\Finance;

use App\Models\Integrations\Banks\BankPayment;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Receipt extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'finance_receipts';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'user_id',
        'bank_payment_id',
        'receipt_number',
        'amount',
        'payment_method',
        'payment_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankPayment(): BelongsTo
    {
        return $this->belongsTo(BankPayment::class);
    }

    /**
     * @return HasMany<InvoiceReceiptAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceReceiptAllocation::class, 'receipt_id');
    }

    /**
     * @return MorphMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'reference');
    }
}
