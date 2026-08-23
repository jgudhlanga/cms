<?php

declare(strict_types=1);

namespace App\Actions\AcademicCalendars;

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddStudentsToAcademicCalendarClassAction
{
    public function __construct(
        private readonly StudentEnrolmentProgressionService $enrolmentProgression,
    ) {}

    /**
     * @param  Collection<int, object>  $eligibleRows
     */
    public function execute(
        AcademicCalendarClass $academicCalendarClass,
        ClassConfig $classConfig,
        Collection $eligibleRows,
        int $tenantId,
    ): int {
        return (int) DB::transaction(function () use ($academicCalendarClass, $classConfig, $eligibleRows, $tenantId): int {
            $added = 0;

            foreach ($eligibleRows as $row) {
                $studentEnrolmentId = (int) $row->student_enrolment_id;
                $studentSemesterId = (int) $row->student_semesters_id;

                $existing = AcademicCalendarStudentEnrolment::withTrashed()
                    ->where('academic_calendar_class_id', $academicCalendarClass->id)
                    ->where('student_enrolment_id', $studentEnrolmentId)
                    ->first();

                if ($existing instanceof AcademicCalendarStudentEnrolment) {
                    if ($existing->trashed()) {
                        $existing->restore();
                        $existing->update([
                            'student_semesters_id' => $studentSemesterId,
                        ]);
                        $this->pinSyllabus($studentEnrolmentId, $classConfig, $studentSemesterId);
                        $added++;
                    }

                    continue;
                }

                AcademicCalendarStudentEnrolment::query()->create([
                    'tenant_id' => $tenantId,
                    'student_enrolment_id' => $studentEnrolmentId,
                    'student_semesters_id' => $studentSemesterId,
                    'academic_calendar_class_id' => $academicCalendarClass->id,
                ]);

                $this->pinSyllabus($studentEnrolmentId, $classConfig, $studentSemesterId);
                $added++;
            }

            return $added;
        });
    }

    private function pinSyllabus(int $studentEnrolmentId, ClassConfig $classConfig, int $studentSemesterId): void
    {
        $enrolment = StudentEnrolment::query()->find($studentEnrolmentId);

        if (! $enrolment instanceof StudentEnrolment) {
            return;
        }

        $studentSemester = $enrolment->studentSemesters()->whereKey($studentSemesterId)->first();

        $this->enrolmentProgression->pinSyllabusIds(
            $enrolment,
            $this->enrolmentProgression->syllabusIdsForClassConfig($classConfig),
            $studentSemester,
        );
    }
}
