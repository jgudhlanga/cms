<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillStudentSemestersService
{
    public function __construct(
        protected SyncStudentSemestersForEnrolmentService $syncStudentSemesters,
    ) {}

    /**
     * @return array{collapsed: int, synced: int, pivots: int}
     */
    public function run(bool $dryRun = false): array
    {
        $collapsed = 0;
        $synced = 0;
        $pivots = 0;

        $groups = $this->duplicateEnrolmentGroups();

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $keeper = $group->sortByDesc('id')->first();

            if (! $keeper instanceof StudentEnrolment) {
                continue;
            }

            foreach ($group as $enrolment) {
                if ((int) $enrolment->id === (int) $keeper->id) {
                    continue;
                }

                if ($dryRun) {
                    $collapsed++;

                    continue;
                }

                $this->snapshotEnrolmentRollback($enrolment, (int) $keeper->id, false);
                $this->repointClassPivots((int) $enrolment->id, (int) $keeper->id);
                $enrolment->delete();
                $collapsed++;
            }
        }

        $enrolments = StudentEnrolment::query()
            ->with(['academicCalendar', 'departmentLevel.level', 'semester', 'studentEnrolmentStatus'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($enrolments as $enrolment) {
            if ($dryRun) {
                $synced++;

                continue;
            }

            $this->snapshotEnrolmentRollback($enrolment);
            $this->syncStudentSemesters->sync($enrolment, null, [
                'sourceSemesterId' => $enrolment->semester_id,
                'sourceStatusId' => $enrolment->student_enrolment_status_id,
                'sourceSyllabusIds' => $enrolment->course_syllabus_ids ?? [],
                'snapshotEnrolment' => true,
            ]);
            $pivots += $this->linkClassPivots($enrolment);
            $synced++;
        }

        return compact('collapsed', 'synced', 'pivots');
    }

    /**
     * @return Collection<int, Collection<int, StudentEnrolment>>
     */
    private function duplicateEnrolmentGroups(): Collection
    {
        return StudentEnrolment::query()
            ->with('academicCalendar')
            ->whereNull('deleted_at')
            ->get()
            ->groupBy(function (StudentEnrolment $enrolment): string {
                $year = $enrolment->academicCalendar?->calendar_year ?? 'unknown';

                return "{$enrolment->student_application_id}_{$year}_{$enrolment->mode_of_study_id}";
            })
            ->filter(fn (Collection $group): bool => $group->count() > 1);
    }

    private function snapshotEnrolmentRollback(
        StudentEnrolment $enrolment,
        ?int $collapsedIntoId = null,
        bool $wasSoftDeleted = false,
    ): void {
        DB::table('student_semester_rollback_enrolments')->updateOrInsert(
            ['enrolment_id' => $enrolment->id],
            [
                'semester_id' => $enrolment->semester_id,
                'student_enrolment_status_id' => $enrolment->student_enrolment_status_id,
                'academic_calendar_id' => $enrolment->academic_calendar_id,
                'course_syllabus_ids' => json_encode($enrolment->course_syllabus_ids),
                'collapsed_into_id' => $collapsedIntoId,
                'was_soft_deleted' => $wasSoftDeleted,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function repointClassPivots(int $fromEnrolmentId, int $toEnrolmentId): void
    {
        $pivots = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $fromEnrolmentId)
            ->get();

        foreach ($pivots as $pivot) {
            DB::table('student_semester_rollback_class_pivots')->updateOrInsert(
                ['academic_calendar_student_enrolment_id' => $pivot->id],
                [
                    'original_student_enrolment_id' => $pivot->student_enrolment_id,
                    'student_semesters_id' => $pivot->student_semesters_id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $pivot->update(['student_enrolment_id' => $toEnrolmentId]);
        }
    }

    private function linkClassPivots(StudentEnrolment $enrolment): int
    {
        $linked = 0;
        $sourceSemesterId = $enrolment->semester_id;

        $pivots = AcademicCalendarStudentEnrolment::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->whereNull('student_semesters_id')
            ->get();

        foreach ($pivots as $pivot) {
            $studentSemester = null;

            if ($sourceSemesterId !== null) {
                $studentSemester = StudentSemester::query()
                    ->where('student_enrolment_id', $enrolment->id)
                    ->where('semester_id', $sourceSemesterId)
                    ->first();
            }

            $studentSemester ??= $enrolment->currentStudentSemester();

            if (! $studentSemester instanceof StudentSemester) {
                continue;
            }

            DB::table('student_semester_rollback_class_pivots')->updateOrInsert(
                ['academic_calendar_student_enrolment_id' => $pivot->id],
                [
                    'original_student_enrolment_id' => $pivot->student_enrolment_id,
                    'student_semesters_id' => $pivot->student_semesters_id,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            $pivot->update(['student_semesters_id' => $studentSemester->id]);
            $linked++;
        }

        return $linked;
    }
}
