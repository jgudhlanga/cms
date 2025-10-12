<?php

namespace App\Models\Students;

use App\Enums\Institution\LevelEnum;
use App\Helpers\WorkflowHelper;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Institution\DepartmentApplicationStep;
use App\Enums\Shared\AcademicLevelEnum;
use App\Models\Shared\{Address, Contact, Country, Gender, IdType, MaritalStatus, NextOfKin, Race, Religion, Title};
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
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

/**
 *
 * @mixin Builder
 * @method static filter(StudentFilter $filters)
 */
class Student extends Model
{
    use HasFactory, SoftDeletes, Filterable, BelongsToTenant, Paginatable, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title_id',
        'gender_id',
        'marital_status_id',
        'race_id',
        'id_type_id',
        'id_number',
        'passport_number',
        'country_id',
        'study_permit_number',
        'student_number',
        'date_of_birth',
        'religion_id',
        'denomination',
        'height',
        'weight',
        'required_exam_sitting_count',
        'disability_status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function idType(): BelongsTo
    {
        return $this->belongsTo(IdType::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(StudentProgram::class, 'student_id');
    }

    public function currentLevel(): ?string
    {
        return $this->programs()->latest()->first()?->levelEnum()?->name();
    }

    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class, 'student_id');
    }

    public function academicRecord(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'student_id');
    }


    public function oLevelResults(): HasMany
    {
        return $this->hasMany(StudentAcademicResult::class, 'student_id')
            ->where('academic_level_id', AcademicLevelEnum::SECONDARY_SCHOOL->id())
            ->select('student_academic_results.*')
            ->distinct('subject_id');
    }


    public function nextOfKins(): MorphMany
    {
        return $this->morphMany(NextOfKin::class, 'kinnable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('Student')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
