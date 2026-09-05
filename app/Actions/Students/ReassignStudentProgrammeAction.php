<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\DTO\Students\ReassignStudentProgrammeDto;
use App\Helpers\EnrolmentHelper;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Institution\CourseLevelModeService;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\ProgrammeOfferingGuard;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;

class ReassignStudentProgrammeAction
{
    public function __construct(
        protected ProgrammeOfferingGuard $offeringGuard,
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected StudentEnrolmentProgressionService $progression,
        protected CourseLevelModeService $courseLevelModes,
    ) {}

    /**
     * @return array{changed: bool, class_unassigned: bool}
     */
    public function execute(StudentApplication $application, ReassignStudentProgrammeDto $target): array
    {
        if ($target->matchesApplication($application)) {
            return ['changed' => false, 'class_unassigned' => false];
        }

        $this->offeringGuard->assert($target);

        return DB::transaction(function () use ($application, $target): array {
            $this->courseLevelModes->ensureMode(
                $target->departmentCourseId,
                $target->departmentLevelId,
                $target->modeOfStudyId,
            );

            $source = [
                'institution_department_id' => (int) $application->institution_department_id,
                'department_level_id' => (int) $application->department_level_id,
                'department_course_id' => (int) $application->department_course_id,
                'mode_of_study_id' => (int) $application->mode_of_study_id,
            ];

            $fields = [
                'institution_department_id' => $target->institutionDepartmentId,
                'department_level_id' => $target->departmentLevelId,
                'department_course_id' => $target->departmentCourseId,
                'mode_of_study_id' => $target->modeOfStudyId,
            ];

            $application->update($fields);

            $classUnassigned = false;

            $enrolments = StudentEnrolment::query()
                ->where('student_application_id', $application->id)
                ->get();

            foreach ($enrolments as $enrolment) {
                $enrolment->update($fields);
                $enrolment->refresh();
                $enrolment->load(['studentSemesters', 'academicCalendar']);
                $this->remapProgrammeSemesters($enrolment);
                $this->progression->pinSyllabusFromMatchingClassConfig($enrolment);
                if ($this->rehomeClassMembership($enrolment)) {
                    $classUnassigned = true;
                }
            }

            $this->reassignExamResults($application, $source, $fields);
            $this->refreshStudentNumber($application, $source['institution_department_id']);

            return ['changed' => true, 'class_unassigned' => $classUnassigned];
        });
    }

    private function remapProgrammeSemesters(StudentEnrolment $enrolment): void
    {
        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        foreach ($enrolment->studentSemesters as $studentSemester) {
            if (! $studentSemester instanceof StudentSemester || $studentSemester->semester_id === null || $dlc === null) {
                $studentSemester->update(['programme_semester_id' => null]);

                continue;
            }

            $mapped = $this->programmeSemesterResolver->mapGlobalSemesterToProgrammeSemester(
                $dlc,
                (int) $studentSemester->semester_id,
            );

            $studentSemester->update(['programme_semester_id' => $mapped?->id]);
        }
    }

    private function rehomeClassMembership(StudentEnrolment $enrolment): bool
    {
        $unassigned = false;
        $pivots = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('is_live', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($pivots as $pivot) {
            $studentSemester = $pivot->student_semesters_id !== null
                ? $enrolment->studentSemesters->firstWhere('id', (int) $pivot->student_semesters_id)
                : $enrolment->currentStudentSemester();

            $config = $this->progression->matchingClassConfig(
                $enrolment,
                $studentSemester instanceof StudentSemester ? $studentSemester : null,
            );
            $targetClass = $config instanceof ClassConfig
                ? AcademicCalendarClass::query()
                    ->where('class_config_id', $config->id)
                    ->orderBy('id')
                    ->first()
                : null;

            if ($targetClass instanceof AcademicCalendarClass) {
                if ((int) $pivot->academic_calendar_class_id !== (int) $targetClass->id) {
                    $pivot->update(['academic_calendar_class_id' => $targetClass->id]);
                }

                continue;
            }

            $pivot->update([
                'is_live' => false,
                'concluded_at' => now(),
            ]);
            $unassigned = true;
        }

        return $unassigned;
    }

    /**
     * @param  array{institution_department_id: int, department_level_id: int, department_course_id: int, mode_of_study_id: int}  $source
     * @param  array{institution_department_id: int, department_level_id: int, department_course_id: int, mode_of_study_id: int}  $target
     */
    private function reassignExamResults(StudentApplication $application, array $source, array $target): void
    {
        StudentExamResult::query()
            ->where('student_id', $application->student_id)
            ->where('institution_department_id', $source['institution_department_id'])
            ->where('department_level_id', $source['department_level_id'])
            ->where('department_course_id', $source['department_course_id'])
            ->where('mode_of_study_id', $source['mode_of_study_id'])
            ->update($target);
    }

    private function refreshStudentNumber(StudentApplication $application, int $previousDepartmentId): void
    {
        if ((int) $application->institution_department_id === $previousDepartmentId) {
            return;
        }

        $application->loadMissing(['student', 'institutionDepartment', 'intakePeriod']);
        $student = $application->student;

        if ($student === null || ! $student->student_number_generated) {
            return;
        }

        $student->update([
            'student_number' => EnrolmentHelper::resolveStudentNumber($application),
        ]);
    }
}
