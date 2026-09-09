<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use App\Services\Institution\ProgrammeSemesterResolver;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SetStudentEnrolmentCurrentPhaseAction
{
    public function __construct(
        protected ProgrammeSemesterResolver $programmeSemesterResolver,
        protected StudentEnrolmentProgressionService $progression,
    ) {}

    public function execute(StudentEnrolment $enrolment, ProgrammeSemester $programmeSemester): StudentEnrolment
    {
        $enrolment->loadMissing([
            'studentSemesters.semester',
            'studentSemesters.programmeSemester',
            'departmentLevel.level',
            'departmentCourse',
            'studentEnrolmentStatus',
        ]);

        $dlc = $this->programmeSemesterResolver->resolveDepartmentLevelCourse($enrolment);

        if ($dlc === null) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_missing_offering'));
        }

        if ((int) $programmeSemester->department_level_course_id !== (int) $dlc->id) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_wrong_offering'));
        }

        $globalSemester = $this->programmeSemesterResolver->calendarSemesterForClassConfig($dlc, $programmeSemester);

        if (! $globalSemester instanceof Semester && $programmeSemester->isTaught()) {
            throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_unmapped'));
        }

        $activeStatusId = $this->progression->statusIdBySlug(StudentEnrolmentProgressionService::STATUS_ACTIVE)
            ?? $enrolment->student_enrolment_status_id;

        return DB::transaction(function () use (
            $enrolment,
            $programmeSemester,
            $globalSemester,
            $activeStatusId,
        ): StudentEnrolment {
            $studentSemester = null;

            if ($globalSemester instanceof Semester) {
                $studentSemester = $enrolment->studentSemesters
                    ->first(fn (StudentSemester $row): bool => (int) $row->semester_id === (int) $globalSemester->id);

                if (! $studentSemester instanceof StudentSemester) {
                    $studentSemester = StudentSemester::query()->create([
                        'student_enrolment_id' => $enrolment->id,
                        'semester_id' => $globalSemester->id,
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId,
                        'course_syllabus_ids' => $enrolment->course_syllabus_ids ?? [],
                    ]);
                } else {
                    $studentSemester->update([
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId ?? $studentSemester->student_enrolment_status_id,
                    ]);
                }

                $enrolment->update([
                    'semester_id' => $globalSemester->id,
                    'student_enrolment_status_id' => $activeStatusId ?? $enrolment->student_enrolment_status_id,
                ]);
            } else {
                // Attachment phases have no global semester; pin by programme_semester_id only.
                $studentSemester = $enrolment->studentSemesters
                    ->first(fn (StudentSemester $row): bool => (int) $row->programme_semester_id === (int) $programmeSemester->id);

                if (! $studentSemester instanceof StudentSemester) {
                    $anchorSemesterId = $enrolment->semester_id
                        ?? $enrolment->studentSemesters->sortByDesc('id')->first()?->semester_id;

                    if ($anchorSemesterId === null) {
                        throw new InvalidArgumentException(__('trans.department_semester_reconciliation_phase_unmapped'));
                    }

                    $studentSemester = StudentSemester::query()->create([
                        'student_enrolment_id' => $enrolment->id,
                        'semester_id' => $anchorSemesterId,
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId,
                        'course_syllabus_ids' => $enrolment->course_syllabus_ids ?? [],
                    ]);
                } else {
                    $studentSemester->update([
                        'programme_semester_id' => $programmeSemester->id,
                        'student_enrolment_status_id' => $activeStatusId ?? $studentSemester->student_enrolment_status_id,
                    ]);
                }
            }

            return $enrolment->fresh([
                'studentSemesters.semester',
                'studentSemesters.programmeSemester',
                'semester',
            ]) ?? $enrolment;
        });
    }
}
