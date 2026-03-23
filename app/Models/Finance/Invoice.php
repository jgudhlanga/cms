<?php

namespace App\Models\Finance;

use App\Enums\Finance\InvoiceStatus;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'finance_invoices';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'fee_type_id',
        'invoice_number',
        'amount',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * @return HasMany<InvoiceReceiptAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceReceiptAllocation::class, 'invoice_id');
    }

    /**
     * @return MorphMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'reference');
    }
}
