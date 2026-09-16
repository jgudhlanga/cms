<?php

namespace App\Models\Students;

use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Http\Filters\Students\StudentApplicationFilter;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\ProgrammeStage;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\WorkflowStep;
use App\Observers\Students\StudentApplicationObserver;
use App\Support\Media\ProofOfPaymentMedia;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Builder
 *
 * @method static filter(StudentApplicationFilter $filters)
 */
#[ObservedBy([StudentApplicationObserver::class])]
class StudentApplication extends Model implements HasMedia
{
    use BelongsToTenant, Filterable, HasFactory, InteractsWithMedia, LogsActivity, Paginatable, SoftDeletes;

    protected $table = 'student_applications';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'institution_department_id',
        'department_level_id',
        'department_course_id',
        'programme_stage_id',
        'required_level_completed',
        'read_write_acknowledged',
        'application_tracking_number',
        'workflow_step_id',
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

    public function transfer(): HasOne
    {
        return $this->hasOne(StudentTransfer::class, 'student_application_id');
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

    public function programmeStage(): BelongsTo
    {
        return $this->belongsTo(ProgrammeStage::class, 'programme_stage_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
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

        $proofOfPaymentMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
        $this->addMediaCollection('application-fee')->acceptsMimeTypes($proofOfPaymentMimeTypes)->useDisk(ProofOfPaymentMedia::DISK);
        $this->addMediaCollection('tuition-fee')->acceptsMimeTypes($proofOfPaymentMimeTypes)->useDisk(ProofOfPaymentMedia::DISK);
    }

    public function hasPaid(FeeTypeEnum $feeType): bool
    {
        return $this->receipt($feeType) !== null;
    }

    public function receipts()
    {
        $this->loadMissing('student.user');

        if ($this->student?->user === null) {
            return Ledger::query()->whereRaw('0 = 1');
        }

        return $this->student->user->ledgers()->with('feeType')->where('type', 'receipt');
    }

    /**
     * Receipt ledgers booked against this application, eager loaded by list views.
     */
    public function receiptLedgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'student_application_id')->where('type', 'receipt');
    }

    public function receipt(FeeTypeEnum $feeType): ?Ledger
    {
        if ($feeType === FeeTypeEnum::APPLICATION_FEE) {
            if ($this->relationLoaded('receiptLedgers')) {
                return $this->latestReceiptFor($this->receiptLedgers->where('payment_status', 'paid'), $feeType);
            }

            return Ledger::query()
                ->where('student_application_id', $this->id)
                ->where('type', 'receipt')
                ->where('payment_status', 'paid')
                ->whereRelation('feeType', 'slug', $feeType->slug())
                ->latest()
                ->first();
        }

        if ($this->relationLoaded('student') && $this->student?->relationLoaded('user') && $this->student->user?->relationLoaded('receiptLedgers')) {
            return $this->latestReceiptFor($this->student->user->receiptLedgers, $feeType);
        }

        return $this->receipts()->whereRelation('feeType', 'slug', $feeType->slug())->latest()->first();
    }

    /**
     * In-memory equivalent of the receipt queries above: newest first, ties broken by id.
     *
     * @param  Collection<int, Ledger>  $ledgers
     */
    private function latestReceiptFor(Collection $ledgers, FeeTypeEnum $feeType): ?Ledger
    {
        return $ledgers
            ->filter(fn (Ledger $ledger): bool => $ledger->feeType?->slug === $feeType->slug())
            ->sortBy([
                fn (Ledger $a, Ledger $b): int => ($b->created_at?->getTimestamp() ?? 0) <=> ($a->created_at?->getTimestamp() ?? 0),
                fn (Ledger $a, Ledger $b): int => $b->id <=> $a->id,
            ])
            ->first();
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
        return $this->levelEnum()?->name();
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(StudentEnrolment::class, 'student_application_id');
    }

    public function classList(): BelongsTo
    {
        return $this->belongsTo(ClassList::class, 'id', 'student_application_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(StudentNote::class, 'noteable');
    }

    public function ledgerTransactions(): MorphMany
    {
        return $this->morphMany(Ledger::class, 'ledgerable')->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('StudentApplication')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
