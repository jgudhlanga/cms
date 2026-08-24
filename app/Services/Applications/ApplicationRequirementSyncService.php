<?php

declare(strict_types=1);

namespace App\Services\Applications;

use App\DTO\Institution\CourseRequirementsDto;
use App\DTO\Institution\DepartmentLevelRequirementsDto;
use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Applications\ApplicationLevelRequirement;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationRequirementSyncService
{
    public function syncLevelRequirement(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel $departmentLevel,
        DepartmentLevelRequirementsDto $dto,
    ): ApplicationLevelRequirement {
        $this->assertLevelBelongsToDepartment($institutionDepartment, $departmentLevel);

        return DB::transaction(function () use ($departmentLevel, $dto, $institutionDepartment) {
            return ApplicationLevelRequirement::query()->updateOrCreate(
                [
                    'tenant_id' => (int) $institutionDepartment->tenant_id,
                    'department_level_id' => (int) $departmentLevel->id,
                ],
                $this->levelFields($dto),
            );
        });
    }

    public function syncCourseRequirement(
        InstitutionDepartment $institutionDepartment,
        DepartmentCourse $departmentCourse,
        CourseRequirementsDto $dto,
    ): ApplicationCourseRequirement {
        $this->assertCourseBelongsToDepartment($institutionDepartment, $departmentCourse);

        $departmentLevel = DepartmentLevel::query()
            ->whereKey($dto->department_level_id)
            ->where('institution_department_id', $institutionDepartment->id)
            ->first();

        if ($departmentLevel === null) {
            throw ValidationException::withMessages([
                'department_level_id' => __('application_requirements.invalid_level_for_department'),
            ]);
        }

        return DB::transaction(function () use ($departmentCourse, $dto, $institutionDepartment) {
            return ApplicationCourseRequirement::query()->updateOrCreate(
                [
                    'tenant_id' => (int) $institutionDepartment->tenant_id,
                    'department_level_id' => (int) $dto->department_level_id,
                    'department_course_id' => (int) $departmentCourse->id,
                ],
                $this->courseFields($dto),
            );
        });
    }

    private function assertLevelBelongsToDepartment(
        InstitutionDepartment $institutionDepartment,
        DepartmentLevel $departmentLevel,
    ): void {
        if ((int) $departmentLevel->institution_department_id !== (int) $institutionDepartment->id) {
            throw ValidationException::withMessages([
                'department_level_id' => __('application_requirements.invalid_level_for_department'),
            ]);
        }
    }

    private function assertCourseBelongsToDepartment(
        InstitutionDepartment $institutionDepartment,
        DepartmentCourse $departmentCourse,
    ): void {
        if ((int) $departmentCourse->institution_department_id !== (int) $institutionDepartment->id) {
            throw ValidationException::withMessages([
                'department_course_id' => __('application_requirements.invalid_course_for_department'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function levelFields(DepartmentLevelRequirementsDto $dto): array
    {
        return [
            'is_o_level_required' => $dto->is_o_level_required,
            'required_subjects_count' => $dto->required_subjects_count,
            'main_subjects_count' => $dto->main_subjects_count,
            'main_subject_ids' => $dto->main_subject_ids,
            'other_subjects_count' => $dto->other_subjects_count,
            'only_read_write_required' => $dto->only_read_write_required,
            'required_level_id' => $dto->required_level_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseFields(CourseRequirementsDto $dto): array
    {
        return [
            'is_o_level_required' => $dto->is_o_level_required,
            'required_subjects_count' => $dto->required_subjects_count,
            'main_subjects_count' => $dto->main_subjects_count,
            'main_subject_ids' => $dto->main_subject_ids,
            'other_subjects_count' => $dto->other_subjects_count,
            'only_read_write_required' => $dto->only_read_write_required,
            'required_level_id' => $dto->required_level_id,
        ];
    }
}
