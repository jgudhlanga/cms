<?php

namespace App\Models\Students;

use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Http\Filters\Students\StudentProgramFilter;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Ledgers\Ledger;
use App\Observers\Students\StudentProgramObserver;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 *
 * @mixin Builder
 * @method static filter(StudentProgramFilter $filters)
 */
#[ObservedBy([StudentProgramObserver::class])]
class StudentProgram extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'institution_department_id',
        'department_level_id',
        'department_course_id',
        'required_level_completed',
        'read_write_acknowledged',
        'application_tracking_number',
        'department_application_step_id',
        'program_status_id',
        'intake_period_id',
        'offer_letter_id',
        'mode_of_study_id',
        'registration_fee_confirmed',
        'tuition_fee_confirmed',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function institutionDepartment(): BelongsTo
    {
        return $this->belongsTo(InstitutionDepartment::class, 'institution_department_id');
    }

    public function departmentLevel(): BelongsTo
    {
        return $this->belongsTo(DepartmentLevel::class, 'department_level_id');
    }

    public function departmentCourse(): BelongsTo
    {
        return $this->belongsTo(DepartmentCourse::class, 'department_course_id');
    }

    public function departmentWorkflowStep(): BelongsTo
    {
        return $this->belongsTo(DepartmentApplicationStep::class, 'department_application_step_id');
    }

    public function intakePeriod(): BelongsTo
    {
        return $this->belongsTo(IntakePeriod::class, 'intake_period_id');
    }

    public function modeOfStudy(): BelongsTo
    {
        return $this->belongsTo(ModeOfStudy::class, 'mode_of_study_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('offer-letter')->singleFile();
    }

    public function hasPaid(FeeTypeEnum $feeType): bool
    {
        return $this->receipt($feeType) !== null;
    }

    public function receipts()
    {
        return $this->student->user->ledgers()->with('feeType')->where('type', 'receipt');
    }

    public function receipt(FeeTypeEnum $feeType): ?Ledger
    {
        return $this->receipts()->whereRelation('feeType', 'slug', $feeType->slug())->latest()->first();
    }


    public function offerLetter(): HasOne
    {
        return $this->hasOne(Media::class, 'id', 'offer_letter_id');
    }

    public function getOfferLetterUrlAttribute(): ?string
    {
        return ($this->offer_letter_id > 0) ? $this->offerLetter->getFullUrl() : null;
    }

    public function levelEnum(): ?LevelEnum
    {
        return $this->departmentLevel?->level?->name ? LevelEnum::from($this->departmentLevel->level->name) : null;
    }

    public function currentLevel(): ?string
    {
        return $this->programs()->latest()->first()?->levelEnum()?->name();
    }

    public function classList(): BelongsTo
    {
        return $this->belongsTo(ClassList::class, 'id', 'student_program_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(StudentNote::class, 'noteable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentProgram')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
