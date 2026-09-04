<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Support\Institution\ProgrammeSemesterNameFormatter;
use Illuminate\Support\Collection;

class StudentCoursePathwayProgressService
{
    public function __construct(
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildForStudent(Student $student): array
    {
        $student->loadMissing([
            'applications.departmentCourse.course',
            'applications.departmentLevel.level',
            'applications.departmentLevel.requirement',
            'enrolments.studentSemesters.studentEnrolmentStatus',
            'enrolments.studentSemesters.semester',
            'enrolments.studentEnrolmentStatus',
            'enrolments.departmentCourse.course',
            'enrolments.departmentLevel.level',
        ]);

        $courseIds = $student->enrolments
            ->pluck('department_course_id')
            ->filter()
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            return [];
        }

        $offerings = DepartmentLevelCourse::query()
            ->whereIn('department_course_id', $courseIds->all())
            ->with([
                'programmeSemesters',
                'departmentCourse.course',
                'departmentCourse.requirements',
                'departmentLevel.level',
                'departmentLevel.requirement',
            ])
            ->get()
            ->groupBy('department_course_id');

        $pathways = [];

        foreach ($courseIds as $courseId) {
            $courseOfferings = $offerings->get((int) $courseId, collect())
                ->sortBy(fn (DepartmentLevelCourse $dlc): int => (int) ($dlc->departmentLevel?->level?->position ?? 0))
                ->values();

            if ($courseOfferings->isEmpty()) {
                continue;
            }

            $pathway = $this->buildPathwayForCourse($student, (int) $courseId, $courseOfferings);

            if ($pathway !== null) {
                $pathways[] = $pathway;
            }
        }

        return $pathways;
    }

    /**
     * @param  Collection<int, DepartmentLevelCourse>  $offerings
     * @return array<string, mixed>|null
     */
    private function buildPathwayForCourse(Student $student, int $departmentCourseId, Collection $offerings): ?array
    {
        $localLevelIds = $this->localDepartmentLevelIds($student, $departmentCourseId);

        if ($localLevelIds === []) {
            return null;
        }

        $includedIndexes = $this->includedOfferingIndexes($offerings, $localLevelIds);
        $localPositions = $offerings
            ->filter(fn (DepartmentLevelCourse $offering): bool => in_array(
                (int) $offering->department_level_id,
                $localLevelIds,
                true,
            ))
            ->map(fn (DepartmentLevelCourse $offering): int => (int) ($offering->departmentLevel?->level?->position ?? 0));
        $minLocalPosition = $localPositions->isEmpty() ? 0 : (int) $localPositions->min();
        $stages = [];

        foreach ($offerings as $index => $offering) {
            if (! isset($includedIndexes[$index])) {
                continue;
            }

            $stages[] = $this->buildStage(
                $student,
                $departmentCourseId,
                $offering,
                $localLevelIds,
                $minLocalPosition,
            );
        }

        if ($stages === []) {
            return null;
        }

        $stepsCompleted = 0;
        $stepsTotal = 0;
        $yearsCompleted = 0.0;
        $yearsTotal = 0.0;

        foreach ($stages as $stage) {
            $stageYears = (float) $stage['years'];
            $yearsTotal += $stageYears;
            $stageSteps = $stage['steps'];
            $stepCount = count($stageSteps);
            $stepsTotal += $stepCount;

            $completedInStage = collect($stageSteps)
                ->filter(fn (array $step): bool => $step['state'] === 'completed')
                ->count();
            $stepsCompleted += $completedInStage;

            if ($stage['status'] === 'completed') {
                $yearsCompleted += $stageYears;
            } elseif ($stepCount > 0) {
                $yearsCompleted += $stageYears * ($completedInStage / $stepCount);
            }
        }

        $first = $offerings->first();

        return [
            'departmentCourseId' => $departmentCourseId,
            'course' => $first?->departmentCourse?->course?->name,
            'yearsCompleted' => round($yearsCompleted, 1),
            'yearsTotal' => round($yearsTotal, 1),
            'stepsCompleted' => $stepsCompleted,
            'stepsTotal' => $stepsTotal,
            'stages' => $stages,
        ];
    }

    /**
     * @param  Collection<int, DepartmentLevelCourse>  $offerings
     * @param  list<int>  $localLevelIds
     * @return array<int, true>
     */
    private function includedOfferingIndexes(Collection $offerings, array $localLevelIds): array
    {
        $included = [];
        $localPositions = [];

        foreach ($offerings as $index => $offering) {
            $levelId = (int) $offering->department_level_id;
            $position = (int) ($offering->departmentLevel?->level?->position ?? $index);

            if (in_array($levelId, $localLevelIds, true)) {
                $included[$index] = true;
                $localPositions[] = $position;
            }
        }

        $minLocalPosition = $localPositions === [] ? null : min($localPositions);

        foreach ($offerings as $index => $offering) {
            if (isset($included[$index])) {
                $this->includeRequiredPriors($offerings, $index, $included);
            }
        }

        if ($minLocalPosition !== null) {
            foreach ($offerings as $index => $offering) {
                $position = (int) ($offering->departmentLevel?->level?->position ?? $index);

                if ($position > $minLocalPosition) {
                    $included[$index] = true;
                }
            }
        }

        return $included;
    }

    /**
     * @param  Collection<int, DepartmentLevelCourse>  $offerings
     * @param  array<int, true>  $included
     */
    private function includeRequiredPriors(Collection $offerings, int $index, array &$included): void
    {
        $guard = 0;
        $current = $offerings->get($index);

        while ($current instanceof DepartmentLevelCourse && $guard < 10) {
            $requiredLevelId = $this->requiredGlobalLevelId($current);

            if ($requiredLevelId === null) {
                return;
            }

            $priorIndex = $offerings->search(
                fn (DepartmentLevelCourse $dlc): bool => (int) ($dlc->departmentLevel?->level_id) === $requiredLevelId,
            );

            if ($priorIndex === false) {
                return;
            }

            $included[(int) $priorIndex] = true;
            $current = $offerings->get((int) $priorIndex);
            $guard++;
        }
    }

    /**
     * @param  list<int>  $localLevelIds
     * @return array<string, mixed>
     */
    private function buildStage(
        Student $student,
        int $departmentCourseId,
        DepartmentLevelCourse $offering,
        array $localLevelIds,
        int $minLocalPosition,
    ): array {
        $hasLocal = in_array((int) $offering->department_level_id, $localLevelIds, true);
        $position = (int) ($offering->departmentLevel?->level?->position ?? 0);
        $levelName = $offering->departmentLevel?->level?->name;
        $impliedComplete = ! $hasLocal && $position < $minLocalPosition;
        $programmeSemesters = $offering->programmeSemesters ?? collect();
        $structureMissing = $programmeSemesters->isEmpty();
        $years = $this->yearsForOffering($offering);

        $application = $student->applications
            ->first(fn (StudentApplication $application): bool => (int) $application->department_course_id === $departmentCourseId
                && (int) $application->department_level_id === (int) $offering->department_level_id);

        if ($impliedComplete) {
            $steps = $programmeSemesters
                ->map(fn (ProgrammeSemester $semester): array => $this->stepPayload($semester, 'completed', $levelName))
                ->values()
                ->all();

            return [
                'departmentLevelId' => (int) $offering->department_level_id,
                'levelName' => $levelName,
                'studentApplicationId' => $application?->id,
                'impliedComplete' => true,
                'structureMissing' => $structureMissing,
                'status' => 'completed',
                'years' => $years,
                'steps' => $steps,
            ];
        }

        if (! $hasLocal) {
            $steps = $programmeSemesters
                ->map(fn (ProgrammeSemester $semester): array => $this->stepPayload($semester, 'locked', $levelName))
                ->values()
                ->all();

            return [
                'departmentLevelId' => (int) $offering->department_level_id,
                'levelName' => $levelName,
                'studentApplicationId' => $application?->id,
                'impliedComplete' => false,
                'structureMissing' => $structureMissing,
                'status' => 'locked',
                'years' => $years,
                'steps' => $steps,
            ];
        }

        $inclusions = $this->inclusionsForOffering($student, $departmentCourseId, (int) $offering->department_level_id);
        $steps = $this->stepsForLocalStage($offering, $inclusions, $levelName);
        $status = $this->stageStatus($offering, $steps, $inclusions);

        return [
            'departmentLevelId' => (int) $offering->department_level_id,
            'levelName' => $levelName,
            'studentApplicationId' => $application?->id,
            'impliedComplete' => false,
            'structureMissing' => $structureMissing,
            'status' => $status,
            'years' => $years,
            'steps' => $steps,
        ];
    }

    /**
     * @param  Collection<int, StudentSemester>  $inclusions
     * @return list<array{programmeSemesterId: int, name: string, kind: string, state: string}>
     */
    private function stepsForLocalStage(DepartmentLevelCourse $offering, Collection $inclusions, ?string $levelName = null): array
    {
        $programmeSemesters = ($offering->programmeSemesters ?? collect())->sortBy('position')->values();

        if ($programmeSemesters->isEmpty()) {
            return [];
        }

        $statusByProgrammeSemesterId = [];

        foreach ($inclusions as $inclusion) {
            $programmeSemester = $this->programmeSemesterResolver->programmeSemesterForStudentSemester($inclusion);

            if (! $programmeSemester instanceof ProgrammeSemester) {
                continue;
            }

            $statusByProgrammeSemesterId[(int) $programmeSemester->id] = $this->progression->statusSlugForSemester($inclusion);
        }

        $laterInclusionExists = static function (int $fromPosition) use ($programmeSemesters, $statusByProgrammeSemesterId): bool {
            return $programmeSemesters
                ->contains(function (ProgrammeSemester $semester) use ($fromPosition, $statusByProgrammeSemesterId): bool {
                    return (int) $semester->position > $fromPosition
                        && array_key_exists((int) $semester->id, $statusByProgrammeSemesterId);
                });
        };

        $steps = [];
        $pastCurrent = false;

        foreach ($programmeSemesters as $programmeSemester) {
            if ($pastCurrent) {
                $steps[] = $this->stepPayload($programmeSemester, 'locked', $levelName);

                continue;
            }

            $slug = $statusByProgrammeSemesterId[(int) $programmeSemester->id] ?? null;

            if ($slug === null) {
                if ($laterInclusionExists((int) $programmeSemester->position)) {
                    $steps[] = $this->stepPayload($programmeSemester, 'completed', $levelName);

                    continue;
                }

                $steps[] = $this->stepPayload($programmeSemester, 'current', $levelName);
                $pastCurrent = true;

                continue;
            }

            if (StudentEnrolmentProgressionService::isBlockingStatus($slug)) {
                $steps[] = $this->stepPayload($programmeSemester, 'blocked', $levelName);
                $pastCurrent = true;

                continue;
            }

            if ($slug === StudentEnrolmentProgressionService::STATUS_AWARD
                || $slug === StudentEnrolmentProgressionService::STATUS_PROCEED) {
                $steps[] = $this->stepPayload($programmeSemester, 'completed', $levelName);

                continue;
            }

            $steps[] = $this->stepPayload($programmeSemester, 'current', $levelName);
            $pastCurrent = true;
        }

        return $steps;
    }

    /**
     * @param  list<array{programmeSemesterId: int, name: string, kind: string, state: string}>  $steps
     * @param  Collection<int, StudentSemester>  $inclusions
     */
    private function stageStatus(
        DepartmentLevelCourse $offering,
        array $steps,
        Collection $inclusions,
    ): string {
        if (collect($steps)->contains(fn (array $step): bool => $step['state'] === 'blocked')) {
            return 'current';
        }

        if (collect($steps)->contains(fn (array $step): bool => $step['state'] === 'current')) {
            return 'current';
        }

        $completion = $this->programmeSemesterResolver->completionProgrammeSemesterForOffering($offering);

        if ($completion instanceof ProgrammeSemester) {
            $completionInclusion = $inclusions->first(
                function (StudentSemester $inclusion) use ($completion): bool {
                    $mapped = $this->programmeSemesterResolver->programmeSemesterForStudentSemester($inclusion);

                    return $mapped instanceof ProgrammeSemester
                        && (int) $mapped->id === (int) $completion->id
                        && $this->progression->statusSlugForSemester($inclusion)
                            === StudentEnrolmentProgressionService::STATUS_AWARD;
                },
            );

            if ($completionInclusion instanceof StudentSemester) {
                return 'completed';
            }
        }

        if ($steps !== [] && collect($steps)->every(fn (array $step): bool => $step['state'] === 'completed')) {
            return 'completed';
        }

        if ($steps === [] && $inclusions->isNotEmpty()) {
            $latest = $inclusions->sortByDesc('id')->first();
            $slug = $latest instanceof StudentSemester
                ? $this->progression->statusSlugForSemester($latest)
                : null;

            if ($slug === StudentEnrolmentProgressionService::STATUS_AWARD) {
                return 'completed';
            }

            return 'current';
        }

        if ($steps !== [] && collect($steps)->every(fn (array $step): bool => $step['state'] === 'locked')) {
            return 'locked';
        }

        return 'locked';
    }

    /**
     * @return Collection<int, StudentSemester>
     */
    private function inclusionsForOffering(Student $student, int $departmentCourseId, int $departmentLevelId): Collection
    {
        return $student->enrolments
            ->filter(fn (StudentEnrolment $enrolment): bool => (int) $enrolment->department_course_id === $departmentCourseId
                && (int) $enrolment->department_level_id === $departmentLevelId)
            ->flatMap(fn (StudentEnrolment $enrolment): Collection => $enrolment->studentSemesters)
            ->filter()
            ->values();
    }

    /**
     * @return list<int>
     */
    private function localDepartmentLevelIds(Student $student, int $departmentCourseId): array
    {
        return $student->enrolments
            ->filter(fn (StudentEnrolment $enrolment): bool => (int) $enrolment->department_course_id === $departmentCourseId)
            ->pluck('department_level_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function yearsForOffering(DepartmentLevelCourse $offering): float
    {
        $periodsPerYear = $this->periodsPerYear($offering);
        $programmeSemesters = $offering->programmeSemesters ?? collect();

        if ($programmeSemesters->isNotEmpty()) {
            return $programmeSemesters->count() / $periodsPerYear;
        }

        $taught = max(0, (int) ($offering->taught_semester_count ?? 0));
        $attachment = (bool) $offering->includes_industrial_attachment
            ? max(0, (int) ($offering->attachment_semester_count ?? 0))
            : 0;
        $fromCounts = ($taught + $attachment) / $periodsPerYear;
        $fromDuration = (float) max(0, $offering->duration_years ?? 0);

        return max($fromDuration, $fromCounts);
    }

    private function periodsPerYear(DepartmentLevelCourse $offering): int
    {
        $calendarType = $offering->departmentLevel?->level?->calendar_type;

        if (! $calendarType instanceof AcademicCalendarTypeEnum) {
            $calendarType = AcademicCalendarTypeEnum::tryFrom((string) $calendarType)
                ?? AcademicCalendarTypeEnum::SEMESTER;
        }

        return max(1, ProgrammeSemesterNameFormatter::periodsPerYear($calendarType));
    }

    private function requiredGlobalLevelId(DepartmentLevelCourse $offering): ?int
    {
        $courseRequirement = $offering->departmentCourse?->requirements
            ?->first(fn ($requirement): bool => (int) $requirement->department_level_id === (int) $offering->department_level_id);

        $required = $courseRequirement?->required_level_id
            ?: $offering->departmentLevel?->requirement?->required_level_id;

        $id = $required !== null ? (int) $required : null;

        return $id !== null && $id > 0 ? $id : null;
    }

    /**
     * @return array{programmeSemesterId: int, name: string, shortName: string, levelName: string|null, kind: string, state: string}
     */
    private function stepPayload(ProgrammeSemester $programmeSemester, string $state, ?string $levelName = null): array
    {
        $kind = $programmeSemester->kind;
        $name = (string) $programmeSemester->name;

        return [
            'programmeSemesterId' => (int) $programmeSemester->id,
            'name' => $name,
            // Every level restarts at Year 1, so the level has to travel with the phase name.
            'shortName' => ProgrammeSemesterNameFormatter::qualifiedName($levelName, $name, short: true),
            'levelName' => $levelName,
            'kind' => is_string($kind) ? $kind : ($kind?->value ?? 'taught'),
            'state' => $state,
        ];
    }
}
