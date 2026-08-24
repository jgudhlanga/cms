<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use Illuminate\Support\Facades\DB;

class RollbackStudentSemestersService
{
    /**
     * @return array{pivots: int, enrolments: int, restored: int, deleted: int}
     */
    public function run(bool $dryRun = false): array
    {
        $pivotRows = DB::table('student_semester_rollback_class_pivots')->get();
        $enrolmentRows = DB::table('student_semester_rollback_enrolments')->get();

        $pivots = 0;
        $enrolments = 0;
        $restored = 0;
        $deleted = 0;

        if ($dryRun) {
            return [
                'pivots' => $pivotRows->count(),
                'enrolments' => $enrolmentRows->count(),
                'restored' => $enrolmentRows->whereNotNull('collapsed_into_id')->where('was_soft_deleted', false)->count(),
                'deleted' => StudentSemester::withTrashed()->count(),
            ];
        }

        return DB::transaction(function () use ($pivotRows, $enrolmentRows, &$pivots, &$enrolments, &$restored, &$deleted): array {
            foreach ($pivotRows as $row) {
                AcademicCalendarStudentEnrolment::query()
                    ->whereKey($row->academic_calendar_student_enrolment_id)
                    ->update([
                        'student_enrolment_id' => $row->original_student_enrolment_id,
                        'student_semesters_id' => null,
                    ]);
                $pivots++;
            }

            foreach ($enrolmentRows as $row) {
                StudentEnrolment::withoutEvents(function () use ($row): void {
                    StudentEnrolment::withTrashed()
                        ->whereKey($row->enrolment_id)
                        ->update([
                            'semester_id' => $row->semester_id,
                            'student_enrolment_status_id' => $row->student_enrolment_status_id,
                            'academic_calendar_id' => $row->academic_calendar_id,
                            'course_syllabus_ids' => $row->course_syllabus_ids !== null
                                ? json_decode((string) $row->course_syllabus_ids, true)
                                : null,
                        ]);
                });
                $enrolments++;
            }

            foreach ($enrolmentRows as $row) {
                if ($row->collapsed_into_id === null || (bool) $row->was_soft_deleted) {
                    continue;
                }

                $enrolment = StudentEnrolment::withTrashed()->find($row->enrolment_id);

                if ($enrolment !== null && $enrolment->trashed()) {
                    $enrolment->restore();
                    $restored++;
                }
            }

            StudentSemester::withTrashed()->forceDelete();
            $deleted = StudentSemester::withTrashed()->count();

            DB::table('student_semester_rollback_class_pivots')->truncate();
            DB::table('student_semester_rollback_enrolments')->truncate();

            return compact('pivots', 'enrolments', 'restored', 'deleted');
        });
    }
}
