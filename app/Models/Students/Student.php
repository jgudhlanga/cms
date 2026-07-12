<?php

namespace App\Models\Students;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Models\Finance\FinanceTransactionQuery;
use App\Models\HMS\HostelApplication;
use App\Models\HMS\HostelLeave;
use App\Models\HMS\HostelQuery;
use App\Models\HMS\HostelRoomAllocation;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\Country;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\NextOfKin;
use App\Models\Shared\Race;
use App\Models\Shared\Religion;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Builder;
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
 * @mixin Builder
 *
 * @method static filter(StudentFilter $filters)
 */
class Student extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

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
        'student_number_generated',
        'date_of_birth',
        'religion_id',
        'denomination',
        'height',
        'weight',
        'required_exam_sitting_count',
        'disability_status',
        'meta_data',
    ];

    protected function casts(): array
    {
        return [
            'meta_data' => 'array',
            'student_number_generated' => 'boolean',
            'date_of_birth' => 'date',
        ];
    }

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

    public function applications(): HasMany
    {
        return $this->hasMany(StudentApplication::class, 'student_id');
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(StudentEnrolment::class, 'student_id');
    }

    public function apprentices(): HasMany
    {
        return $this->hasMany(StudentApprentice::class, 'student_id');
    }

    public function latestEnrolment(): HasOne
    {
        return $this->hasOne(StudentEnrolment::class)->latestOfMany();
    }

    public function latestApplication(): HasOne
    {
        return $this->hasOne(StudentApplication::class)->latestOfMany();
    }

    public function currentLevel(): ?string
    {
        return $this->applications()->latest()->first()?->levelEnum()?->name();
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

    public function setIdNumberAttribute($value)
    {
        $this->attributes['id_number'] = $value ?: null;
    }

    public function isZimbabwean(): bool
    {
        return $this->id_type_id === IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(StudentNote::class, 'noteable');
    }

    public function hostelApplications(): HasMany
    {
        return $this->hasMany(HostelApplication::class, 'student_id');
    }

    public function hostelRoomAllocations(): HasMany
    {
        return $this->hasMany(HostelRoomAllocation::class, 'student_id');
    }

    public function activeHostelAllocation(): HasOne
    {
        return $this->hasOne(HostelRoomAllocation::class, 'student_id')
            ->where('status', HostelAllocationStatusEnum::ACTIVE->value)
            ->latestOfMany();
    }

    public function hostelQueries(): HasMany
    {
        return $this->hasMany(HostelQuery::class, 'student_id');
    }

    public function hostelLeaves(): HasMany
    {
        return $this->hasMany(HostelLeave::class, 'student_id');
    }

    public function financeTransactionQueries(): HasMany
    {
        return $this->hasMany(FinanceTransactionQuery::class, 'student_id');
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
