<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingLevel;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RegistrationProgrammeAvailabilityService
{
    public function __construct(
        protected ApplicationEligibilityService $eligibility,
    ) {}

    /**
     * Compact programme tree for guest programme finder.
     *
     * @return array{
     *     available: bool,
     *     departments: list<array{
     *         id: int,
     *         name: string,
     *         available: bool,
     *         levels: list<array{
     *             id: int,
     *             levelId: int,
     *             name: string,
     *             available: bool,
     *             courses: list<array{
     *                 id: int,
     *                 departmentCourseId: int,
     *                 name: string,
     *                 available: bool,
     *                 modes: list<array{id: int, name: string, available: bool}>
     *             }>
     *         }>
     *     }>,
     *     unavailableReason: string|null
     * }
     */
    public function programmeTree(
        ApplicationTrackEnum $track,
        int $levelId,
        ?string $continuousFocus = null,
    ): array {
        $institutionLevel = Level::query()->findOrFail($levelId);

        $departmentLevels = $this->offeredDepartmentLevelsForInstitutionLevel($levelId);

        if ($track === ApplicationTrackEnum::Continuous) {
            $departmentLevels = $this->filterDepartmentLevelsForContinuousFocus(
                $departmentLevels,
                $institutionLevel,
                $continuousFocus
            );
        }

        if ($track === ApplicationTrackEnum::Apprentice) {
            $departmentLevels = $departmentLevels->filter(function (DepartmentLevel $dl) {
                return ApplicationOfferingLevel::query()
                    ->where('department_level_id', $dl->id)
                    ->whereHas('offeringDepartment', fn ($q) => $q->where('has_apprentice_programmes', true))
                    ->exists();
            })->values();
        }

        $ojetModeId = ModeOfStudy::query()->where('name', ModeOfStudyEnum::OJET->value)->value('id');
        $ojetModeId = $ojetModeId !== null ? (int) $ojetModeId : null;

        $departments = [];

        foreach ($departmentLevels->groupBy('institution_department_id') as $institutionDepartmentId => $levels) {
            $first = $levels->first();
            $deptName = $first?->institutionDepartment?->department?->name
                ?? $first?->institutionDepartment?->name
                ?? 'Department';

            $levelNodes = [];

            foreach ($levels as $departmentLevel) {
                $courses = $this->offeredCoursesForDepartmentLevel(
                    $departmentLevel,
                    $track,
                    $continuousFocus,
                    $ojetModeId,
                );

                $levelNodes[] = [
                    'id' => (int) $departmentLevel->id,
                    'levelId' => (int) $departmentLevel->level_id,
                    'name' => (string) ($departmentLevel->level?->name ?? ''),
                    'available' => count($courses) > 0,
                    'courses' => $courses,
                ];
            }

            $availableLevels = array_values(array_filter($levelNodes, fn (array $node) => $node['available']));

            if ($availableLevels === []) {
                continue;
            }

            $departments[] = [
                'id' => (int) $institutionDepartmentId,
                'name' => (string) $deptName,
                'available' => true,
                'levels' => $availableLevels,
            ];
        }

        $available = $departments !== [];

        return [
            'available' => $available,
            'departments' => $departments,
            'unavailableReason' => $available
                ? null
                : ($track === ApplicationTrackEnum::Apprentice
                    ? __('trans.registration_programme_none_available_apprentice', [
                        'level' => $institutionLevel->name,
                    ])
                    : __('trans.registration_programme_none_available', [
                        'level' => $institutionLevel->name,
                    ])),
        ];
    }

    public function hasAvailableProgrammes(
        ApplicationTrackEnum $track,
        int $levelId,
        ?string $continuousFocus = null,
    ): bool {
        return $this->programmeTree($track, $levelId, $continuousFocus)['available'];
    }

    public function isDepartmentLevelOffered(DepartmentLevel $departmentLevel): bool
    {
        return ApplicationOfferingLevel::query()
            ->where('department_level_id', $departmentLevel->id)
            ->exists();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function offeredModesForCourseLevel(int $departmentCourseId, int $departmentLevelId): array
    {
        $modeIds = ApplicationOfferingMode::query()
            ->whereHas('offeringCourse', function ($query) use ($departmentCourseId, $departmentLevelId) {
                $query->where('department_course_id', $departmentCourseId)
                    ->whereHas('offeringLevel', fn ($q) => $q->where('department_level_id', $departmentLevelId));
            })
            ->pluck('mode_of_study_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($modeIds === []) {
            return [];
        }

        return ModeOfStudy::query()
            ->whereIn('id', $modeIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ModeOfStudy $mode): array => [
                'id' => (int) $mode->id,
                'name' => (string) $mode->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @throws ValidationException
     */
    public function assertProgrammeSelection(
        ApplicationTrackEnum $track,
        int $levelId,
        int $departmentId,
        int $departmentLevelId,
        int $courseId,
        int $modeOfStudyId,
        ?string $continuousFocus = null,
    ): void {
        $tree = $this->programmeTree($track, $levelId, $continuousFocus);

        if (! $tree['available']) {
            throw ValidationException::withMessages([
                'department_id' => $tree['unavailableReason'] ?? __('trans.registration_programme_none_available', ['level' => '']),
            ]);
        }

        $department = collect($tree['departments'])->firstWhere('id', $departmentId);

        if ($department === null) {
            throw ValidationException::withMessages([
                'department_id' => __('trans.registration_programme_invalid_selection'),
            ]);
        }

        $level = collect($department['levels'])->firstWhere('id', $departmentLevelId);

        if ($level === null) {
            throw ValidationException::withMessages([
                'department_level_id' => __('trans.registration_programme_invalid_selection'),
            ]);
        }

        $course = collect($level['courses'])->firstWhere('departmentCourseId', $courseId)
            ?? collect($level['courses'])->firstWhere('id', $courseId);

        if ($course === null) {
            throw ValidationException::withMessages([
                'course_id' => __('trans.registration_programme_invalid_selection'),
            ]);
        }

        $mode = collect($course['modes'])->firstWhere('id', $modeOfStudyId);

        if ($mode === null) {
            throw ValidationException::withMessages([
                'mode_of_study_id' => __('trans.registration_programme_invalid_selection'),
            ]);
        }
    }

    /**
     * @return Collection<int, DepartmentLevel>
     */
    private function offeredDepartmentLevelsForInstitutionLevel(int $levelId): Collection
    {
        $departmentLevelIds = ApplicationOfferingLevel::query()
            ->whereHas('departmentLevel', fn ($q) => $q->where('level_id', $levelId))
            ->pluck('department_level_id')
            ->unique()
            ->all();

        if ($departmentLevelIds === []) {
            return collect();
        }

        return DepartmentLevel::query()
            ->with(['level', 'institutionDepartment.department'])
            ->whereIn('id', $departmentLevelIds)
            ->get();
    }

    /**
     * @param  Collection<int, DepartmentLevel>  $departmentLevels
     * @return Collection<int, DepartmentLevel>
     */
    private function filterDepartmentLevelsForContinuousFocus(
        Collection $departmentLevels,
        Level $institutionLevel,
        ?string $continuousFocus,
    ): Collection {
        if ($continuousFocus === 'sdp') {
            if (! $this->eligibility->isSdpLevel($institutionLevel)) {
                return collect();
            }

            return $departmentLevels;
        }

        if ($continuousFocus === 'ojet') {
            $ojetModeId = ModeOfStudy::query()->where('name', ModeOfStudyEnum::OJET->value)->value('id');

            if ($ojetModeId === null) {
                return collect();
            }

            $eligibleDepartmentLevelIds = ApplicationOfferingMode::query()
                ->where('mode_of_study_id', $ojetModeId)
                ->with('offeringCourse.offeringLevel')
                ->get()
                ->map(fn (ApplicationOfferingMode $mode) => (int) ($mode->offeringCourse?->offeringLevel?->department_level_id ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->all();

            return $departmentLevels
                ->filter(fn (DepartmentLevel $dl) => in_array($dl->id, $eligibleDepartmentLevelIds, true))
                ->values();
        }

        return $departmentLevels->filter(
            fn (DepartmentLevel $dl) => $this->eligibility->isLevelEligibleForContinuous($institutionLevel)
        )->values();
    }

    /**
     * @return list<array{
     *     id: int,
     *     departmentCourseId: int,
     *     name: string,
     *     available: bool,
     *     modes: list<array{id: int, name: string, available: bool}>
     * }>
     */
    private function offeredCoursesForDepartmentLevel(
        DepartmentLevel $departmentLevel,
        ApplicationTrackEnum $track,
        ?string $continuousFocus,
        ?int $ojetModeId,
    ): array {
        $offeringCourses = ApplicationOfferingCourse::query()
            ->with(['departmentCourse.course', 'modes.modeOfStudy', 'offeringLevel'])
            ->whereHas('offeringLevel', fn ($q) => $q->where('department_level_id', $departmentLevel->id))
            ->get();

        $courses = [];

        foreach ($offeringCourses as $offeringCourse) {
            $departmentCourse = $offeringCourse->departmentCourse;
            if ($departmentCourse === null) {
                continue;
            }

            $modes = $this->filterModesForTrack(
                $offeringCourse->modes
                    ->map(fn (ApplicationOfferingMode $mode) => $mode->modeOfStudy)
                    ->filter()
                    ->values(),
                $track,
                $continuousFocus,
                $ojetModeId,
            );

            if ($modes === []) {
                continue;
            }

            $pivot = DepartmentLevelCourse::query()
                ->where('department_level_id', $departmentLevel->id)
                ->where('department_course_id', $departmentCourse->id)
                ->first();

            $courses[] = [
                'id' => (int) ($pivot?->id ?? $offeringCourse->id),
                'departmentCourseId' => (int) $departmentCourse->id,
                'name' => (string) ($departmentCourse->course?->name ?? ''),
                'available' => true,
                'modes' => $modes,
            ];
        }

        return $courses;
    }

    /**
     * @param  Collection<int, ModeOfStudy>  $modes
     * @return list<array{id: int, name: string, available: bool}>
     */
    private function filterModesForTrack(
        Collection $modes,
        ApplicationTrackEnum $track,
        ?string $continuousFocus,
        ?int $ojetModeId,
    ): array {
        $result = [];

        foreach ($modes->sortBy('name') as $mode) {
            if ($track === ApplicationTrackEnum::Continuous && $continuousFocus === 'ojet') {
                if ($ojetModeId === null || (int) $mode->id !== $ojetModeId) {
                    continue;
                }
            }

            if ($track === ApplicationTrackEnum::Continuous && $continuousFocus === 'sdp') {
                if ($ojetModeId !== null && (int) $mode->id === $ojetModeId) {
                    continue;
                }
            }

            $result[] = [
                'id' => (int) $mode->id,
                'name' => (string) $mode->name,
                'available' => true,
            ];
        }

        return $result;
    }
}
