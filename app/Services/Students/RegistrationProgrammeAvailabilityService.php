<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Institution\CourseLevelMode;
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

        $departmentLevels = DepartmentLevel::query()
            ->with(['level', 'institutionDepartment.department'])
            ->where('level_id', $levelId)
            ->where('show_on_current_application_period', true)
            ->get();

        if ($track === ApplicationTrackEnum::Continuous) {
            $departmentLevels = $this->filterDepartmentLevelsForContinuousFocus(
                $departmentLevels,
                $institutionLevel,
                $continuousFocus
            );
        }

        $ojetModeId = ModeOfStudy::query()->where('name', ModeOfStudyEnum::OJET->value)->value('id');
        $ojetModeId = $ojetModeId !== null ? (int) $ojetModeId : null;

        $blockReleaseModeId = ModeOfStudy::query()->where('name', ModeOfStudyEnum::BLOCK_RELEASE->value)->value('id');
        $blockReleaseModeId = $blockReleaseModeId !== null ? (int) $blockReleaseModeId : null;

        $departments = [];

        foreach ($departmentLevels->groupBy('institution_department_id') as $institutionDepartmentId => $levels) {
            $first = $levels->first();
            $deptName = $first?->institutionDepartment?->department?->name
                ?? $first?->institutionDepartment?->name
                ?? 'Department';

            $levelNodes = [];

            foreach ($levels as $departmentLevel) {
                $courses = $this->coursesForDepartmentLevel(
                    $departmentLevel,
                    $track,
                    $continuousFocus,
                    $ojetModeId,
                    $blockReleaseModeId,
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

            $eligibleDepartmentLevelIds = CourseLevelMode::query()
                ->get()
                ->filter(fn (CourseLevelMode $row) => in_array((int) $ojetModeId, array_map('intval', $row->modes ?? []), true))
                ->pluck('department_level_id')
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
    private function coursesForDepartmentLevel(
        DepartmentLevel $departmentLevel,
        ApplicationTrackEnum $track,
        ?string $continuousFocus,
        ?int $ojetModeId,
        ?int $blockReleaseModeId,
    ): array {
        $levelCourses = DepartmentLevelCourse::query()
            ->with(['departmentCourse.course'])
            ->where('department_level_id', $departmentLevel->id)
            ->whereHas('departmentCourse', fn ($q) => $q->where('show_on_current_application_period', true))
            ->get();

        $courses = [];

        foreach ($levelCourses as $levelCourse) {
            $departmentCourse = $levelCourse->departmentCourse;

            if ($departmentCourse === null) {
                continue;
            }

            $modes = $this->modesForCourse(
                (int) $departmentCourse->id,
                (int) $departmentLevel->id,
                $track,
                $continuousFocus,
                $ojetModeId,
                $blockReleaseModeId,
            );

            if ($modes === []) {
                continue;
            }

            $courses[] = [
                'id' => (int) $levelCourse->id,
                'departmentCourseId' => (int) $departmentCourse->id,
                'name' => (string) ($departmentCourse->course?->name ?? ''),
                'available' => true,
                'modes' => $modes,
            ];
        }

        return $courses;
    }

    /**
     * @return list<array{id: int, name: string, available: bool}>
     */
    private function modesForCourse(
        int $departmentCourseId,
        int $departmentLevelId,
        ApplicationTrackEnum $track,
        ?string $continuousFocus,
        ?int $ojetModeId,
        ?int $blockReleaseModeId,
    ): array {
        $courseLevelMode = CourseLevelMode::query()
            ->where('department_course_id', $departmentCourseId)
            ->where('department_level_id', $departmentLevelId)
            ->first();

        $modeIds = array_map('intval', $courseLevelMode?->modes ?? []);

        if ($modeIds === []) {
            return [];
        }

        $modes = ModeOfStudy::query()->whereIn('id', $modeIds)->orderBy('name')->get();

        $result = [];

        foreach ($modes as $mode) {
            if ($track === ApplicationTrackEnum::Apprentice) {
                if ($blockReleaseModeId === null || (int) $mode->id !== $blockReleaseModeId) {
                    continue;
                }
            }

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
