<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use Illuminate\Support\Collection;

class StudentEnrolmentProgressionService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_AWARD = 'award';

    public const STATUS_DEFERRED = 'deferred';

    public const STATUS_DISQUALIFIED = 'disqualified';

    public const STATUS_PROCEED = 'proceed';

    public const STATUS_REFERRED = 'referred';

    public const STATUS_UNKNOWN = 'unknown';

    /** @deprecated Use STATUS_AWARD instead */
    public const STATUS_COMPLETED = 'award';

    /** @deprecated Use STATUS_REFERRED instead */
    public const STATUS_REPEAT = 'referred';

    /**
     * Statuses that block the student from proceeding to the next semester.
     *
     * @var list<string>
     */
    public const BLOCKING_STATUSES = [
        self::STATUS_ABSENT,
        self::STATUS_DEFERRED,
        self::STATUS_DISQUALIFIED,
        self::STATUS_REFERRED,
    ];

    public function __construct(
        protected StudentSemesterPhaseResolver $phaseResolver,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
    ) {}

    /**
     * @return Collection<int, Semester>
     */
    public function phaseOptions(AcademicCalendarTypeEnum $type): Collection
    {
        return $this->phaseResolver->phaseOptions($type);
    }

    public function existingPhaseCount(StudentApplication $studentApplication): int
    {
        return (int) StudentSemester::query()
            ->whereHas('enrolment', function ($query) use ($studentApplication): void {
                $query
                    ->where('student_application_id', $studentApplication->id)
                    ->whereNull('deleted_at');
            })
            ->whereNull('deleted_at')
            ->count();
    }

    public function currentStudentSemester(StudentEnrolment $enrolment): ?StudentSemester
    {
        return $enrolment->currentStudentSemester();
    }

    public function statusSlug(StudentEnrolment $enrolment): ?string
    {
        $studentSemester = $this->currentStudentSemester($enrolment);
        $studentSemester?->loadMissing('studentEnrolmentStatus');

        $slug = $studentSemester?->studentEnrolmentStatus?->slug
            ?? $enrolment->studentEnrolmentStatus?->slug;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function statusSlugForSemester(StudentSemester $studentSemester): ?string
    {
        $studentSemester->loadMissing('studentEnrolmentStatus');
        $slug = $studentSemester->studentEnrolmentStatus?->slug;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function isLastPhase(StudentEnrolment $enrolment): bool
    {
        $current = $this->currentStudentSemester($enrolment);

        return $this->isLastPhaseSemester($enrolment, $current);
    }

    public function isLastPhaseSemester(StudentEnrolment $enrolment, ?StudentSemester $studentSemester): bool
    {
        $studentSemester ??= $this->currentStudentSemester($enrolment);

        if ($studentSemester instanceof StudentSemester) {
            $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

            if ($dlc !== null && $dlc->programmeSemesters->isNotEmpty()) {
                return $this->programmeSemesterResolver->isLastProgrammeSemester($studentSemester);
            }
        }

        $enrolment->loadMissing(['studentApplication.departmentLevel.level', 'departmentLevel.level']);

        $calendarType = $enrolment->studentApplication?->departmentLevel?->level?->calendar_type
            ?? $enrolment->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            return false;
        }

        $semesterId = $studentSemester?->semester_id ?? $enrolment->semester_id;

        return $this->isLastPhaseSemesterId(
            $semesterId !== null ? (int) $semesterId : null,
            $calendarType,
        );
    }

    public function isLastPhaseSemesterId(?int $semesterId, AcademicCalendarTypeEnum $type): bool
    {
        if ($semesterId === null) {
            return false;
        }

        $last = $this->phaseOptions($type)->last();

        if (! $last instanceof Semester) {
            return false;
        }

        return $semesterId === (int) $last->id;
    }

    public function canAdvanceToNextPhase(StudentEnrolment $enrolment): bool
    {
        $slug = $this->statusSlug($enrolment);
        $current = $this->currentStudentSemester($enrolment);
        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        $isLast = $dlc !== null && $dlc->programmeSemesters->isNotEmpty() && $current instanceof StudentSemester
            ? $this->programmeSemesterResolver->isLastProgrammeSemester($current)
            : $this->isLastPhase($enrolment);

        return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_PROCEED)
            && ! $isLast;
    }

    public function canCompleteLevel(StudentEnrolment $enrolment): bool
    {
        $slug = $this->statusSlug($enrolment);
        $current = $this->currentStudentSemester($enrolment);

        if ($current instanceof StudentSemester) {
            $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

            if ($dlc !== null && $dlc->programmeSemesters->isNotEmpty()) {
                return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_AWARD)
                    && $this->programmeSemesterResolver->isCompletionProgrammeSemester($current);
            }
        }

        return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_AWARD)
            && $this->isLastPhase($enrolment);
    }

    public function canCompleteLevelSemester(StudentSemester $studentSemester): bool
    {
        $studentSemester->loadMissing('enrolment');
        $enrolment = $studentSemester->enrolment;

        if (! $enrolment instanceof StudentEnrolment) {
            return false;
        }

        $slug = $this->statusSlugForSemester($studentSemester);
        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc !== null && $dlc->programmeSemesters->isNotEmpty()) {
            return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_AWARD)
                && $this->programmeSemesterResolver->isCompletionProgrammeSemester($studentSemester);
        }

        return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_AWARD)
            && $this->isLastPhaseSemester($enrolment, $studentSemester);
    }

    public function canApplyToNextLevel(StudentEnrolment $enrolment): bool
    {
        return $this->statusSlug($enrolment) === self::STATUS_AWARD
            && $this->hasFurtherDepartmentLevel($enrolment);
    }

    public function hasFurtherDepartmentLevel(StudentEnrolment $enrolment): bool
    {
        $enrolment->loadMissing(['departmentLevel.level', 'studentApplication.departmentLevel.level']);

        $departmentLevel = $enrolment->departmentLevel ?? $enrolment->studentApplication?->departmentLevel;

        if (! $departmentLevel instanceof DepartmentLevel) {
            return false;
        }

        $position = (int) ($departmentLevel->level?->position ?? 0);
        $departmentId = (int) $departmentLevel->institution_department_id;

        return DepartmentLevel::query()
            ->where('institution_department_id', $departmentId)
            ->whereNull('deleted_at')
            ->whereHas('level', function ($query) use ($position): void {
                $query->where('position', '>', $position)->whereNull('deleted_at');
            })
            ->exists();
    }

    public function statusIdBySlug(string $slug): ?int
    {
        $candidates = match ($slug) {
            'repeat-re-write', 'repeatre-write' => ['repeat-re-write', 'repeatre-write', 'referred'],
            'deferred-postponed', 'deferredpostponed' => ['deferred-postponed', 'deferredpostponed', 'deferred'],
            'completed' => ['completed', 'award'],
            default => [$slug],
        };

        $id = StudentEnrolmentStatus::query()->whereIn('slug', $candidates)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * @return list<int>
     */
    public function syllabusIdsForClassConfig(?ClassConfig $classConfig): array
    {
        if (! $classConfig instanceof ClassConfig) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $classConfig->course_syllabus_ids ?? []),
            static fn (int $id): bool => $id > 0,
        )));
    }

    public function matchingClassConfig(StudentEnrolment $enrolment, ?StudentSemester $studentSemester = null): ?ClassConfig
    {
        $enrolment->loadMissing('academicCalendar');
        $studentSemester ??= $this->currentStudentSemester($enrolment);

        $calendarYear = $enrolment->academicCalendar?->calendar_year;
        $programmeSemesterId = $studentSemester?->programme_semester_id;

        if ($programmeSemesterId === null && $studentSemester instanceof StudentSemester) {
            $programmeSemester = $this->programmeSemesterResolver->programmeSemesterForStudentSemester($studentSemester);
            $programmeSemesterId = $programmeSemester?->id;
        }

        $query = ClassConfig::query()
            ->where('institution_department_id', $enrolment->institution_department_id)
            ->where('department_course_id', $enrolment->department_course_id)
            ->where('department_level_id', $enrolment->department_level_id)
            ->where('mode_of_study_id', $enrolment->mode_of_study_id)
            ->when(
                is_string($calendarYear) && $calendarYear !== '',
                fn ($q) => $q->where('calendar_year', $calendarYear),
            );

        if ($programmeSemesterId !== null) {
            return $query
                ->where('programme_semester_id', $programmeSemesterId)
                ->where('slug', 'standard')
                ->first()
                ?? $query->where('programme_semester_id', $programmeSemesterId)->first();
        }

        $semesterId = $studentSemester?->semester_id ?? $enrolment->semester_id;

        return $query->where('semester_id', $semesterId)->first();
    }

    /**
     * @param  list<int>  $syllabusIds
     */
    public function pinSyllabusIds(StudentEnrolment $enrolment, array $syllabusIds, ?StudentSemester $studentSemester = null): void
    {
        $studentSemester ??= $this->currentStudentSemester($enrolment);

        $normalized = array_values(array_unique(array_filter(
            $syllabusIds,
            static fn (int $id): bool => $id > 0,
        )));

        if ($studentSemester instanceof StudentSemester) {
            $studentSemester->update(['course_syllabus_ids' => $normalized]);
        }

        StudentEnrolment::withoutEvents(function () use ($enrolment, $normalized): void {
            $enrolment->update(['course_syllabus_ids' => $normalized]);
        });
    }

    public function pinSyllabusFromMatchingClassConfig(StudentEnrolment $enrolment, ?StudentSemester $studentSemester = null): void
    {
        $studentSemester ??= $this->currentStudentSemester($enrolment);
        $ids = $this->syllabusIdsForClassConfig($this->matchingClassConfig($enrolment, $studentSemester));

        if ($ids === []) {
            return;
        }

        $this->pinSyllabusIds($enrolment, $ids, $studentSemester);
    }

    public function updateEnrolmentStatus(StudentEnrolment $enrolment, int $statusId, ?StudentSemester $studentSemester = null): void
    {
        $studentSemester ??= $this->currentStudentSemester($enrolment);

        if ($studentSemester instanceof StudentSemester) {
            $studentSemester->update(['student_enrolment_status_id' => $statusId]);
        }

        StudentEnrolment::withoutEvents(function () use ($enrolment, $statusId): void {
            $enrolment->update(['student_enrolment_status_id' => $statusId]);
        });
    }

    public function updateStudentSemesterStatus(StudentSemester $studentSemester, int $statusId): void
    {
        $studentSemester->loadMissing('enrolment');
        $studentSemester->update(['student_enrolment_status_id' => $statusId]);

        $enrolment = $studentSemester->enrolment;

        if ($enrolment instanceof StudentEnrolment) {
            app(SyncStudentSemestersForEnrolmentService::class)
                ->snapshotLatestPhaseOntoEnrolment($enrolment);
        }
    }

    /**
     * @deprecated Use updateEnrolmentStatus() for per-semester status updates.
     */
    public function syncStatusForApplication(StudentApplication $studentApplication, int $statusId): void
    {
        StudentSemester::query()
            ->whereHas('enrolment', fn ($query) => $query
                ->where('student_application_id', $studentApplication->id)
                ->whereNull('deleted_at'))
            ->update(['student_enrolment_status_id' => $statusId]);
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    public function availableStatuses(): array
    {
        return StudentEnrolmentStatus::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(fn (StudentEnrolmentStatus $status): array => [
                'slug' => (string) $status->slug,
                'name' => (string) $status->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $slugs
     */
    public static function isBlockingStatus(?string $slug): bool
    {
        return $slug !== null && in_array($slug, self::BLOCKING_STATUSES, true);
    }

    public function nextPhaseSemester(StudentEnrolment $enrolment): ?Semester
    {
        $current = $this->currentStudentSemester($enrolment);

        if ($current instanceof StudentSemester) {
            $nextProgrammeSemester = $this->nextProgrammeSemester($current);

            if ($nextProgrammeSemester instanceof ProgrammeSemester) {
                $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

                if ($dlc !== null) {
                    return $this->programmeSemesterResolver->globalSemesterForProgrammeSemester($dlc, $nextProgrammeSemester);
                }
            }
        }

        $enrolment->loadMissing(['departmentLevel.level', 'studentApplication.departmentLevel.level']);

        $calendarType = $enrolment->studentApplication?->departmentLevel?->level?->calendar_type
            ?? $enrolment->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            return null;
        }

        $options = $this->phaseOptions($calendarType)->values();
        $currentIndex = null;

        foreach ($options as $index => $option) {
            if ($current !== null && (int) $option->id === (int) $current->semester_id) {
                $currentIndex = $index;

                break;
            }
        }

        if ($currentIndex === null) {
            return $options->get(1);
        }

        return $options->get($currentIndex + 1);
    }

    public function nextProgrammeSemester(?StudentSemester $current): ?ProgrammeSemester
    {
        if (! $current instanceof StudentSemester) {
            return null;
        }

        return $this->programmeSemesterResolver->nextProgrammeSemester($current);
    }
}
