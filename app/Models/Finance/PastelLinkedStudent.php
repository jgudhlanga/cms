<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\Institution\IntakePeriod;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Paginatable;
use Database\Factories\Finance\PastelLinkedStudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastelLinkedStudent extends Model
{
    use BelongsToTenant, HasFactory, Paginatable;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'student_number',
        'intake_period_id',
        'linked_by',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
        ];
    }

    protected static function newFactory(): PastelLinkedStudentFactory
    {
        return PastelLinkedStudentFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }

    public function intakePeriod(): BelongsTo
    {
        return $this->belongsTo(IntakePeriod::class);
    }
}
