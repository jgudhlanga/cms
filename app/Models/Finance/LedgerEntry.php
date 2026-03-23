<?php

namespace App\Models\Finance;

use App\Enums\Finance\LedgerTransactionType;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'finance_ledger_entries';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'user_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'account_code',
        'debit',
        'credit',
        'transaction_date',
        'description',
        'currency',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'transaction_type' => LedgerTransactionType::class,
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'transaction_date' => 'date',
            'exchange_rate' => 'decimal:6',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
