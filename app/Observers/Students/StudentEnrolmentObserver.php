<?php

declare(strict_types=1);

namespace App\Observers\Students;

use App\Models\Students\StudentEnrolment;
use App\Services\Students\SyncStudentSemestersForEnrolmentService;

class StudentEnrolmentObserver
{
    public function __construct(
        protected SyncStudentSemestersForEnrolmentService $syncStudentSemesters,
    ) {}

    public function created(StudentEnrolment $enrolment): void
    {
        if ($enrolment->semester_id === null || $enrolment->student_enrolment_status_id === null) {
            return;
        }

        $this->syncStudentSemesters->sync($enrolment, null, [
            'sourceSemesterId' => $enrolment->semester_id,
            'sourceStatusId' => $enrolment->student_enrolment_status_id,
            'sourceSyllabusIds' => $enrolment->course_syllabus_ids ?? [],
            'snapshotEnrolment' => false,
        ]);
    }

    public function updated(StudentEnrolment $enrolment): void
    {
        if (! $enrolment->wasChanged(['semester_id', 'student_enrolment_status_id', 'course_syllabus_ids'])) {
            return;
        }

        if ($enrolment->semester_id === null || $enrolment->student_enrolment_status_id === null) {
            return;
        }

        $this->syncStudentSemesters->sync($enrolment, null, [
            'sourceSemesterId' => $enrolment->semester_id,
            'sourceStatusId' => $enrolment->student_enrolment_status_id,
            'sourceSyllabusIds' => $enrolment->course_syllabus_ids ?? [],
            'snapshotEnrolment' => false,
        ]);
    }
}
