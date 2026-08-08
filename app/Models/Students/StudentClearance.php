<?php

declare(strict_types=1);

namespace App\Models\Students;

use App\Enums\Students\StudentClearanceSection;
use App\Models\AcademicCalendars\Semester;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentClearance extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'calendar_year',
        'semester_id',
        'accounts_cleared',
        'accounts_cleared_by',
        'accounts_cleared_at',
        'accounts_notes',
        'library_cleared',
        'library_cleared_by',
        'library_cleared_at',
        'library_notes',
        'security_cleared',
        'security_cleared_by',
        'security_cleared_at',
        'security_notes',
        'hostel_cleared',
        'hostel_cleared_by',
        'hostel_cleared_at',
        'hostel_notes',
        'department_cleared',
        'department_cleared_by',
        'department_cleared_at',
        'department_notes',
    ];

    protected function casts(): array
    {
        return [
            'calendar_year' => 'integer',
            'accounts_cleared' => 'boolean',
            'accounts_cleared_at' => 'datetime',
            'library_cleared' => 'boolean',
            'library_cleared_at' => 'datetime',
            'security_cleared' => 'boolean',
            'security_cleared_at' => 'datetime',
            'hostel_cleared' => 'boolean',
            'hostel_cleared_at' => 'datetime',
            'department_cleared' => 'boolean',
            'department_cleared_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function accountsClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounts_cleared_by');
    }

    public function libraryClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'library_cleared_by');
    }

    public function securityClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'security_cleared_by');
    }

    public function hostelClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hostel_cleared_by');
    }

    public function departmentClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_cleared_by');
    }

    public function isAccountsCleared(): bool
    {
        return (bool) $this->accounts_cleared;
    }

    public function isFullyCleared(): bool
    {
        foreach (StudentClearanceSection::all() as $section) {
            if (! (bool) $this->getAttribute($section->clearedColumn())) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function pendingSections(): array
    {
        $pending = [];

        foreach (StudentClearanceSection::all() as $section) {
            if (! (bool) $this->getAttribute($section->clearedColumn())) {
                $pending[] = $section->value;
            }
        }

        return $pending;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentClearance')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
