<?php

declare(strict_types=1);

namespace App\Services\Applications;

use App\Models\Applications\ApplicationOfferingCourse;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Applications\ApplicationOfferingLevel;
use App\Models\Applications\ApplicationOfferingMode;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationOfferingSyncService
{
    /**
     * @param  array{
     *     enabled: bool,
     *     has_apprentice_programmes: bool,
     *     levels: list<array{
     *         department_level_id: int,
     *         courses: list<array{
     *             department_course_id: int,
     *             mode_of_study_ids: list<int>
     *         }>
     *     }>
     * }  $payload
     */
    public function sync(InstitutionDepartment $institutionDepartment, array $payload): ApplicationOfferingDepartment
    {
        $tenantId = (int) $institutionDepartment->tenant_id;
        $enabled = (bool) ($payload['enabled'] ?? false);

        return DB::transaction(function () use ($institutionDepartment, $payload, $tenantId, $enabled) {
            if (! $enabled) {
                ApplicationOfferingDepartment::query()
                    ->where('institution_department_id', $institutionDepartment->id)
                    ->get()
                    ->each(function (ApplicationOfferingDepartment $offering): void {
                        $this->deleteOfferingTree($offering);
                    });

                return ApplicationOfferingDepartment::make([
                    'institution_department_id' => $institutionDepartment->id,
                    'has_apprentice_programmes' => false,
                ]);
            }

            $levels = $payload['levels'] ?? [];
            $this->assertValidTree($institutionDepartment, $levels);

            $offering = ApplicationOfferingDepartment::withTrashed()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'institution_department_id' => $institutionDepartment->id,
                ],
                [
                    'has_apprentice_programmes' => (bool) ($payload['has_apprentice_programmes'] ?? false),
                ],
            );

            if ($offering->trashed()) {
                $offering->restore();
            }

            $keptLevelIds = [];

            foreach ($levels as $levelPayload) {
                $departmentLevelId = (int) $levelPayload['department_level_id'];
                $offeringLevel = ApplicationOfferingLevel::withTrashed()->updateOrCreate(
                    [
                        'application_offering_department_id' => $offering->id,
                        'department_level_id' => $departmentLevelId,
                    ],
                    ['tenant_id' => $tenantId],
                );

                if ($offeringLevel->trashed()) {
                    $offeringLevel->restore();
                }

                $keptLevelIds[] = (int) $offeringLevel->id;
                $keptCourseIds = [];

                foreach ($levelPayload['courses'] ?? [] as $coursePayload) {
                    $departmentCourseId = (int) $coursePayload['department_course_id'];
                    $modeIds = array_values(array_unique(array_map(
                        'intval',
                        $coursePayload['mode_of_study_ids'] ?? [],
                    )));

                    if ($modeIds === []) {
                        throw ValidationException::withMessages([
                            'levels' => __('application_offerings.no_modes_selected'),
                        ]);
                    }

                    $offeringCourse = ApplicationOfferingCourse::withTrashed()->updateOrCreate(
                        [
                            'application_offering_level_id' => $offeringLevel->id,
                            'department_course_id' => $departmentCourseId,
                        ],
                        ['tenant_id' => $tenantId],
                    );

                    if ($offeringCourse->trashed()) {
                        $offeringCourse->restore();
                    }

                    $keptCourseIds[] = (int) $offeringCourse->id;
                    $keptModeIds = [];

                    foreach ($modeIds as $modeId) {
                        $offeringMode = ApplicationOfferingMode::withTrashed()->updateOrCreate(
                            [
                                'application_offering_course_id' => $offeringCourse->id,
                                'mode_of_study_id' => $modeId,
                            ],
                            ['tenant_id' => $tenantId],
                        );

                        if ($offeringMode->trashed()) {
                            $offeringMode->restore();
                        }

                        $keptModeIds[] = (int) $offeringMode->id;
                    }

                    ApplicationOfferingMode::query()
                        ->where('application_offering_course_id', $offeringCourse->id)
                        ->whereNotIn('id', $keptModeIds)
                        ->get()
                        ->each->delete();
                }

                ApplicationOfferingCourse::query()
                    ->where('application_offering_level_id', $offeringLevel->id)
                    ->whereNotIn('id', $keptCourseIds)
                    ->get()
                    ->each(function (ApplicationOfferingCourse $course): void {
                        $course->modes()->get()->each->delete();
                        $course->delete();
                    });
            }

            ApplicationOfferingLevel::query()
                ->where('application_offering_department_id', $offering->id)
                ->whereNotIn('id', $keptLevelIds)
                ->get()
                ->each(function (ApplicationOfferingLevel $level): void {
                    foreach ($level->courses as $course) {
                        $course->modes()->get()->each->delete();
                        $course->delete();
                    }
                    $level->delete();
                });

            return $offering->fresh(['levels.courses.modes']) ?? $offering;
        });
    }

    private function deleteOfferingTree(ApplicationOfferingDepartment $offering): void
    {
        $offering->loadMissing('levels.courses.modes');

        foreach ($offering->levels as $level) {
            foreach ($level->courses as $course) {
                $course->modes()->get()->each->delete();
                $course->delete();
            }
            $level->delete();
        }

        $offering->delete();
    }

    /**
     * @param  list<array{department_level_id: int, courses: list<array{department_course_id: int, mode_of_study_ids: list<int>}>}>  $levels
     */
    private function assertValidTree(InstitutionDepartment $institutionDepartment, array $levels): void
    {
        $linkedLevelIds = $institutionDepartment->departmentLevels()->pluck('id')->map(fn ($id) => (int) $id)->all();

        foreach ($levels as $levelPayload) {
            $departmentLevelId = (int) $levelPayload['department_level_id'];

            if (! in_array($departmentLevelId, $linkedLevelIds, true)) {
                throw ValidationException::withMessages([
                    'levels' => __('application_offerings.invalid_level'),
                ]);
            }

            $linkedCourseIds = DepartmentLevelCourse::query()
                ->where('department_level_id', $departmentLevelId)
                ->pluck('department_course_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($levelPayload['courses'] ?? [] as $coursePayload) {
                $departmentCourseId = (int) $coursePayload['department_course_id'];

                if (! in_array($departmentCourseId, $linkedCourseIds, true)) {
                    throw ValidationException::withMessages([
                        'levels' => __('application_offerings.invalid_course'),
                    ]);
                }

                if (($coursePayload['mode_of_study_ids'] ?? []) === []) {
                    throw ValidationException::withMessages([
                        'levels' => __('application_offerings.no_modes_selected'),
                    ]);
                }
            }
        }
    }
}
