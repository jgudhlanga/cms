<?php

namespace App\Models\Finance;

use App\Enums\Finance\JournalType;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Journal extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'finance_journals';

    protected $fillable = [
        'tenant_id',
        'journal_type',
        'description',
        'journal_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'journal_type' => JournalType::class,
            'journal_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return MorphMany<LedgerEntry, $this>
     */
    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'reference');
    }
}
