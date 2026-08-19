<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
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

    /**
     * @return Collection<int, Semester>
     */
    public function phaseOptions(AcademicCalendarTypeEnum $type): Collection
    {
        $prefix = $type->value;

        return Semester::query()
            ->where('slug', 'like', "{$prefix}-%")
            ->get()
            ->sortBy(function (Semester $option): int {
                $parts = explode('-', (string) $option->slug);

                return (int) end($parts);
            })
            ->values();
    }

    public function existingPhaseCount(StudentApplication $studentApplication): int
    {
        return (int) StudentEnrolment::query()
            ->where('student_application_id', $studentApplication->id)
            ->whereNull('deleted_at')
            ->count();
    }

    public function statusSlug(StudentEnrolment $enrolment): ?string
    {
        $enrolment->loadMissing('studentEnrolmentStatus');

        $slug = $enrolment->studentEnrolmentStatus?->slug;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function isLastPhase(StudentEnrolment $enrolment): bool
    {
        $enrolment->loadMissing(['studentApplication.departmentLevel.level', 'semester']);

        $calendarType = $enrolment->studentApplication?->departmentLevel?->level?->calendar_type
            ?? $enrolment->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $enrolment->loadMissing('departmentLevel.level');
            $calendarType = $enrolment->departmentLevel?->level?->calendar_type;
        }

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            return false;
        }

        return $this->isLastPhaseSemesterId(
            $enrolment->semester_id !== null ? (int) $enrolment->semester_id : null,
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

        return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_PROCEED)
            && ! $this->isLastPhase($enrolment);
    }

    public function canCompleteLevel(StudentEnrolment $enrolment): bool
    {
        $slug = $this->statusSlug($enrolment);

        return ($slug === self::STATUS_ACTIVE || $slug === self::STATUS_AWARD)
            && $this->isLastPhase($enrolment);
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

    public function matchingClassConfig(StudentEnrolment $enrolment): ?ClassConfig
    {
        $enrolment->loadMissing('academicCalendar');

        $calendarYear = $enrolment->academicCalendar?->calendar_year;

        return ClassConfig::query()
            ->where('institution_department_id', $enrolment->institution_department_id)
            ->where('department_course_id', $enrolment->department_course_id)
            ->where('department_level_id', $enrolment->department_level_id)
            ->where('mode_of_study_id', $enrolment->mode_of_study_id)
            ->where('semester_id', $enrolment->semester_id)
            ->when(
                is_string($calendarYear) && $calendarYear !== '',
                fn ($query) => $query->where('calendar_year', $calendarYear),
            )
            ->first();
    }

    /**
     * @param  list<int>  $syllabusIds
     */
    public function pinSyllabusIds(StudentEnrolment $enrolment, array $syllabusIds): void
    {
        $enrolment->update([
            'course_syllabus_ids' => array_values(array_unique(array_filter(
                $syllabusIds,
                static fn (int $id): bool => $id > 0,
            ))),
        ]);
    }

    public function pinSyllabusFromMatchingClassConfig(StudentEnrolment $enrolment): void
    {
        $ids = $this->syllabusIdsForClassConfig($this->matchingClassConfig($enrolment));

        if ($ids === []) {
            return;
        }

        $this->pinSyllabusIds($enrolment, $ids);
    }

    public function updateEnrolmentStatus(StudentEnrolment $enrolment, int $statusId): void
    {
        $enrolment->update(['student_enrolment_status_id' => $statusId]);
    }

    /**
     * @deprecated Use updateEnrolmentStatus() for per-semester status updates.
     */
    public function syncStatusForApplication(StudentApplication $studentApplication, int $statusId): void
    {
        StudentEnrolment::query()
            ->where('student_application_id', $studentApplication->id)
            ->whereNull('deleted_at')
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
}
