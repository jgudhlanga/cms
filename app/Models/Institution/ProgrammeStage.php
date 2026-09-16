<?php

declare(strict_types=1);

namespace App\Models\Institution;

use App\Enums\Institution\ProgrammeSemesterKindEnum;
use App\Models\Students\StudentProgrammeStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProgrammeStage extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'department_level_course_id',
        'position',
        'stage_number',
        'code',
        'name',
        'kind',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'stage_number' => 'integer',
            'kind' => ProgrammeSemesterKindEnum::class,
        ];
    }

    public function departmentLevelCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevelCourse::class, 'department_level_course_id');
    }

    public function programmeSemesters(): HasMany
    {
        return $this->hasMany(ProgrammeSemester::class, 'programme_stage_id')
            ->orderBy('position');
    }

    public function studentProgrammeStages(): HasMany
    {
        return $this->hasMany(StudentProgrammeStage::class, 'programme_stage_id');
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('ProgrammeStage')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
