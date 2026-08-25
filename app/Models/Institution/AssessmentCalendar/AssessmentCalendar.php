<?php

namespace App\Models\Institution\AssessmentCalendar;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Http\Filters\Institution\AssessmentCalendarFilter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\AssessmentType;
use App\Traits\BelongsToTenant;
use App\Traits\Filterable;
use App\Traits\Paginatable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\Institution\AssessmentCalendarFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @mixin Builder
 *
 * @method static filter(AssessmentCalendarFilter $filters)
 */
class AssessmentCalendar extends Model
{
    use BelongsToTenant, Filterable, HasFactory, LogsActivity, Paginatable, SoftDeletes;

    public const int DEFAULT_FIRST_NOTIFICATION_DAYS = 10;

    public const int DEFAULT_SECOND_NOTIFICATION_DAYS = 5;

    public const int DEFAULT_DUE_NOTIFICATION_DAYS = 0;

    protected $fillable = [
        'tenant_id',
        'assessment_type_id',
        'academic_calendar_id',
        'start_date',
        'end_date',
        'first_notification_days_before',
        'second_notification_days_before',
        'due_notification_days_before',
        'type',
    ];

    protected static function newFactory(): AssessmentCalendarFactory
    {
        return AssessmentCalendarFactory::new();
    }

    protected function casts(): array
    {
        return [
            'type' => AcademicCalendarTypeEnum::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'first_notification_days_before' => 'integer',
            'second_notification_days_before' => 'integer',
            'due_notification_days_before' => 'integer',
        ];
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }

    /**
     * @return Attribute<Carbon|null, never>
     */
    protected function firstNotificationDate(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->notificationDate($this->daysBeforeFor(MissingMarksNotificationTierEnum::First)));
    }

    /**
     * @return Attribute<Carbon|null, never>
     */
    protected function secondNotificationDate(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->notificationDate($this->daysBeforeFor(MissingMarksNotificationTierEnum::Second)));
    }

    /**
     * @return Attribute<Carbon|null, never>
     */
    protected function dueNotificationDate(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->notificationDate($this->daysBeforeFor(MissingMarksNotificationTierEnum::Due)));
    }

    public function daysBeforeFor(MissingMarksNotificationTierEnum $tier): int
    {
        return match ($tier) {
            MissingMarksNotificationTierEnum::First => (int) ($this->first_notification_days_before ?? self::DEFAULT_FIRST_NOTIFICATION_DAYS),
            MissingMarksNotificationTierEnum::Second => (int) ($this->second_notification_days_before ?? self::DEFAULT_SECOND_NOTIFICATION_DAYS),
            MissingMarksNotificationTierEnum::Due => (int) ($this->due_notification_days_before ?? self::DEFAULT_DUE_NOTIFICATION_DAYS),
        };
    }

    public function notificationDate(int $daysBefore): ?Carbon
    {
        if (! $this->end_date instanceof CarbonInterface) {
            return null;
        }

        return Carbon::parse($this->end_date)->startOfDay()->subDays($daysBefore);
    }

    public function matchingTier(CarbonInterface $today): ?MissingMarksNotificationTierEnum
    {
        $todayStart = Carbon::parse($today)->startOfDay();

        foreach (MissingMarksNotificationTierEnum::cases() as $tier) {
            $date = $this->notificationDate($this->daysBeforeFor($tier));

            if ($date instanceof Carbon && $date->equalTo($todayStart)) {
                return $tier;
            }
        }

        return null;
    }

    public function isInNotificationWindow(CarbonInterface $today): bool
    {
        $todayStart = Carbon::parse($today)->startOfDay();
        $firstDate = $this->first_notification_date;
        $endDate = $this->end_date;

        if (! $firstDate instanceof CarbonInterface || ! $endDate instanceof CarbonInterface) {
            return false;
        }

        return $todayStart->betweenIncluded(
            Carbon::parse($firstDate)->startOfDay(),
            Carbon::parse($endDate)->startOfDay(),
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('AssessmentCalendar')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
