<?php

declare(strict_types=1);

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/*
 * Fixtures for the study position confirmation feature. Builds on the department reconciliation
 * context, but with a real two-semester year so "current period" and "current slot" mean something.
 */

/**
 * @return array<string, mixed> the department reconciliation context plus
 *                              semesterOne, semesterTwo, slotOne, slotTwo and statuses
 */
function makeStudyPositionContext(string $today = '2026-09-10'): array
{
    Carbon::setTestNow(Carbon::parse($today));
    $year = Carbon::now()->format('Y');

    // Created first so the reconciliation context's firstOrCreate picks up Semester 1.
    $semesterOne = AcademicCalendar::query()->create([
        'calendar_year' => $year,
        'type' => 'semester',
        'opening_date' => "{$year}-02-01",
        'closing_date' => "{$year}-06-30",
    ]);
    $semesterTwo = AcademicCalendar::query()->create([
        'calendar_year' => $year,
        'type' => 'semester',
        'opening_date' => "{$year}-08-01",
        'closing_date' => "{$year}-12-10",
    ]);

    $context = makeDepartmentReconciliationContext();

    $statuses = [];

    foreach (['Active', 'Award', 'Deferred', 'Disqualified', 'Proceed', 'Referred'] as $name) {
        $statuses[Str::lower($name)] = StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }

    return $context + [
        'semesterOne' => $semesterOne,
        'semesterTwo' => $semesterTwo,
        'slotOne' => Semester::query()->where('slug', 'semester-1')->firstOrFail(),
        'slotTwo' => Semester::query()->where('slug', 'semester-2')->firstOrFail(),
        'statuses' => $statuses,
    ];
}

/**
 * Adds another year's enrolment for the same student and application, holding exactly the given pins.
 *
 * @param  list<array{slug: string, phase: ProgrammeSemester, status?: int}>  $phaseRows
 */
function addStudyPositionYearEnrolment(
    StudentEnrolment $template,
    AcademicCalendar $calendar,
    array $phaseRows,
): StudentEnrolment {
    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $template->student_id,
        'student_application_id' => $template->student_application_id,
        'institution_department_id' => $template->institution_department_id,
        'department_level_id' => $template->department_level_id,
        'department_course_id' => $template->department_course_id,
        'semester_id' => $template->semester_id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $template->mode_of_study_id,
        'student_enrolment_status_id' => $template->student_enrolment_status_id,
    ]);

    StudentSemester::query()->where('student_enrolment_id', $enrolment->id)->forceDelete();

    foreach ($phaseRows as $row) {
        StudentSemester::query()->create([
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => Semester::query()->where('slug', $row['slug'])->value('id'),
            'programme_semester_id' => $row['phase']->id,
            'student_enrolment_status_id' => $row['status'] ?? $template->student_enrolment_status_id,
            'course_syllabus_ids' => [],
        ]);
    }

    return $enrolment->fresh(['studentSemesters.semester', 'studentSemesters.programmeSemester']);
}

/**
 * @return array<string, int|null> slot slug => pinned programme_semester_id
 */
function studyPositionPins(StudentEnrolment $enrolment): array
{
    return StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->join('semesters', 'semesters.id', '=', 'student_semesters.semester_id')
        ->orderBy('semesters.slug')
        ->pluck('student_semesters.programme_semester_id', 'semesters.slug')
        ->map(fn ($id): ?int => $id !== null ? (int) $id : null)
        ->all();
}
