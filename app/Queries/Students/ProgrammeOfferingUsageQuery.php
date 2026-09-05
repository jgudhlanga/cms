<?php

declare(strict_types=1);

namespace App\Queries\Students;

use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProgrammeOfferingUsageQuery
{
    /**
     * @param  list<int>  $modeOfStudyIds
     * @return list<array{
     *     application_id: int,
     *     student_enrolment_id: int|null,
     *     student_name: string,
     *     institution_department_id: int|null,
     *     department: string|null,
     *     department_level_id: int|null,
     *     level: string|null,
     *     department_course_id: int|null,
     *     course: string|null,
     *     mode_of_study_id: int|null,
     *     mode_of_study: string|null,
     *     intake_period: string|null,
     *     has_enrolment: bool
     * }>
     */
    public function records(int $departmentCourseId, int $departmentLevelId, array $modeOfStudyIds = []): array
    {
        $applications = $this->baseQuery()
            ->where('department_course_id', $departmentCourseId)
            ->where('department_level_id', $departmentLevelId)
            ->when(
                $modeOfStudyIds !== [],
                fn ($query) => $query->whereIn('mode_of_study_id', $modeOfStudyIds),
            )
            ->orderBy('id')
            ->get();

        return $this->mapRecords($applications);
    }

    /**
     * @param  list<int>  $applicationIds
     * @param  list<int>  $studentEnrolmentIds
     * @return list<array{
     *     application_id: int,
     *     student_enrolment_id: int|null,
     *     student_name: string,
     *     institution_department_id: int|null,
     *     department: string|null,
     *     department_level_id: int|null,
     *     level: string|null,
     *     department_course_id: int|null,
     *     course: string|null,
     *     mode_of_study_id: int|null,
     *     mode_of_study: string|null,
     *     intake_period: string|null,
     *     has_enrolment: bool
     * }>
     */
    public function recordsForIds(array $applicationIds, array $studentEnrolmentIds = []): array
    {
        $fromEnrolments = $studentEnrolmentIds === []
            ? []
            : StudentEnrolment::query()
                ->whereIn('id', $studentEnrolmentIds)
                ->pluck('student_application_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

        $ids = array_values(array_unique(array_filter(
            [...$applicationIds, ...$fromEnrolments],
            fn (int $id): bool => $id > 0,
        )));

        $applications = $this->baseQuery()
            ->whereIn('id', $ids === [] ? [0] : $ids)
            ->orderBy('id')
            ->get();

        return $this->mapRecords($applications);
    }

    /**
     * @return Builder<StudentApplication>
     */
    private function baseQuery(): Builder
    {
        return StudentApplication::query()->with([
            'student.user',
            'institutionDepartment.department',
            'departmentLevel.level',
            'departmentCourse.course',
            'modeOfStudy',
            'intakePeriod',
        ]);
    }

    /**
     * @param  Collection<int, StudentApplication>  $applications
     * @return list<array{
     *     application_id: int,
     *     student_enrolment_id: int|null,
     *     student_name: string,
     *     institution_department_id: int|null,
     *     department: string|null,
     *     department_level_id: int|null,
     *     level: string|null,
     *     department_course_id: int|null,
     *     course: string|null,
     *     mode_of_study_id: int|null,
     *     mode_of_study: string|null,
     *     intake_period: string|null,
     *     has_enrolment: bool
     * }>
     */
    private function mapRecords(Collection $applications): array
    {
        $enrolmentsByApplication = StudentEnrolment::query()
            ->whereIn('student_application_id', $applications->pluck('id')->all() ?: [0])
            ->get()
            ->groupBy('student_application_id');

        return $applications
            ->map(function (StudentApplication $application) use ($enrolmentsByApplication): array {
                $enrolment = $enrolmentsByApplication->get($application->id)?->first();

                return [
                    'application_id' => (int) $application->id,
                    'student_enrolment_id' => $enrolment instanceof StudentEnrolment ? (int) $enrolment->id : null,
                    'student_name' => trim((string) ($application->student?->user?->full_name ?? '')),
                    'institution_department_id' => $application->institution_department_id !== null
                        ? (int) $application->institution_department_id
                        : null,
                    'department' => $application->institutionDepartment?->department?->name,
                    'department_level_id' => $application->department_level_id !== null
                        ? (int) $application->department_level_id
                        : null,
                    'level' => $application->departmentLevel?->level?->name,
                    'department_course_id' => $application->department_course_id !== null
                        ? (int) $application->department_course_id
                        : null,
                    'course' => $application->departmentCourse?->course?->name,
                    'mode_of_study_id' => $application->mode_of_study_id !== null ? (int) $application->mode_of_study_id : null,
                    'mode_of_study' => $application->modeOfStudy?->name,
                    'intake_period' => $application->intakePeriod?->name,
                    'has_enrolment' => $enrolment instanceof StudentEnrolment,
                ];
            })
            ->values()
            ->all();
    }
}
