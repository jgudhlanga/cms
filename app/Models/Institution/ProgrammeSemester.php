<?php

declare(strict_types=1);

namespace App\Models\Institution;

use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\Students\StudentSemester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProgrammeSemester extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_level_course_id',
        'programme_stage_id',
        'position',
        'year_number',
        'period_in_year',
        'name',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'year_number' => 'integer',
            'period_in_year' => 'integer',
            'kind' => ProgrammeSemesterKindEnum::class,
        ];
    }

    public function departmentLevelCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevelCourse::class, 'department_level_course_id');
    }

    public function programmeStage(): BelongsTo
    {
        return $this->belongsTo(ProgrammeStage::class, 'programme_stage_id');
    }

    public function isTaught(): bool
    {
        return $this->kind === ProgrammeSemesterKindEnum::TAUGHT
            || $this->kind === null;
    }

    public function isIndustrialAttachment(): bool
    {
        return $this->kind === ProgrammeSemesterKindEnum::INDUSTRIAL_ATTACHMENT;
    }

    /**
     * Industrial attachment periods belong to OJET alone, and OJET offers nothing but them.
     */
    public function isOfferedInMode(bool $isOjetMode): bool
    {
        return $isOjetMode ? $this->isIndustrialAttachment() : $this->isTaught();
    }

    public function studentSemesters(): HasMany
    {
        return $this->hasMany(StudentSemester::class, 'programme_semester_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ProgrammeSemester')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
