<?php

declare(strict_types=1);

namespace App\Observers\Setup;

use App\Enums\Setup\SetupGapCheckEnum;
use App\Jobs\Setup\RecheckSetupGapsJob;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassConfigLecturerInCharge;
use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingLevel;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\Division;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Database\Eloquent\Model;

/**
 * Closes setup alerts the moment the configuration behind them is corrected.
 *
 * Saving the screen that fixes a problem re-runs only the checks that problem belongs to, so the alert
 * disappears on its own — nobody has to run the nightly scan by hand. The nightly run stays as the
 * backstop for the problems no screen owns (an application's mode being reassigned, a hostel bed being
 * allocated), which are too high-volume to hang a re-check on.
 */
class SetupGapRecheckObserver
{
    public function saved(Model $model): void
    {
        $this->recheck($model);
    }

    public function deleted(Model $model): void
    {
        $this->recheck($model);
    }

    public function restored(Model $model): void
    {
        $this->recheck($model);
    }

    /**
     * Which models this observer watches, and the checks each one can change.
     *
     * @return array<class-string, list<SetupGapCheckEnum>>
     */
    public static function watched(): array
    {
        return [
            CourseLevelMode::class => [
                SetupGapCheckEnum::APPLICATIONS_IN_UNCONFIGURED_MODE,
                SetupGapCheckEnum::COURSE_LEVEL_WITHOUT_MODES,
                SetupGapCheckEnum::MODES_ON_UNLINKED_LEVEL,
                SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED,
            ],
            DepartmentLevelCourse::class => [
                SetupGapCheckEnum::MODES_ON_UNLINKED_LEVEL,
                SetupGapCheckEnum::COURSE_LEVEL_WITHOUT_MODES,
                SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED,
            ],
            DepartmentCourse::class => [
                SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD,
            ],
            DepartmentLevel::class => [
                SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD,
            ],
            ApplicationOfferingLevel::class => [
                SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED,
                SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD,
            ],
            ApplicationOfferingCourse::class => [
                SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED,
                SetupGapCheckEnum::OFFERING_COURSE_LEVEL_UNLINKED,
                SetupGapCheckEnum::OFFERING_REFERENCES_DELETED_RECORD,
            ],
            ApplicationOfferingMode::class => [
                SetupGapCheckEnum::OFFERING_MODE_NOT_CONFIGURED,
            ],
            ClassConfig::class => [
                SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE,
                SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_SYLLABUS,
                SetupGapCheckEnum::DEPARTMENT_ASSESSMENT_CALENDAR_MISSING,
            ],
            ClassConfigLecturerInCharge::class => [
                SetupGapCheckEnum::CLASS_CONFIG_WITHOUT_LECTURER_IN_CHARGE,
            ],
            Division::class => [
                SetupGapCheckEnum::DIVISION_WITHOUT_HEAD,
                SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION,
            ],
            InstitutionDepartment::class => [
                SetupGapCheckEnum::DEPARTMENTS_WITHOUT_DIVISION,
            ],
            AssessmentCalendar::class => [
                SetupGapCheckEnum::ASSESSMENT_CALENDAR_DATES_MISSING,
                SetupGapCheckEnum::DEPARTMENT_ASSESSMENT_CALENDAR_MISSING,
            ],
            DepartmentAssessmentCalendar::class => [
                SetupGapCheckEnum::DEPARTMENT_ASSESSMENT_CALENDAR_MISSING,
            ],
        ];
    }

    private function recheck(Model $model): void
    {
        $checks = self::watched()[$model::class] ?? [];

        if ($checks === []) {
            return;
        }

        RecheckSetupGapsJob::dispatch(
            array_map(static fn (SetupGapCheckEnum $check): string => $check->value, $checks),
        );
    }
}
