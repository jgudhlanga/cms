<?php

declare(strict_types=1);

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Models\Users\User;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['Semester 1', 'Semester 2'] as $name) {
        Semester::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Award', 'Absent', 'Deferred', 'Disqualified', 'Proceed', 'Referred', 'Unknown'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }

    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

/**
 * @param  string  $semesterSlug  the phase the student starts on; the enrolment is anchored to the
 *                                matching calendar period, since phases are no longer backfilled
 *                                from the start of the year for a mid-year intake.
 */
function createEnrolmentStatusUpdateContext(string $studentNumber, string $semesterSlug = 'semester-2'): array
{
    $studentApplication = createVerifiedStudentApplication($studentNumber);
    $studentApplication->departmentLevel->level->update(['calendar_type' => 'semester']);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-06-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-31',
    ]);

    $statusId = (int) StudentEnrolmentStatus::query()->where('slug', 'unknown')->value('id');
    $semesterTwoId = (int) Semester::query()->where('slug', $semesterSlug)->value('id');
    $calendar = AcademicCalendar::query()
        ->where('calendar_year', '2026')
        ->where('type', 'semester')
        ->where('opening_date', $semesterSlug === 'semester-1' ? '2026-01-01' : '2026-07-01')
        ->firstOrFail();

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $studentApplication->student_id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'semester_id' => $semesterTwoId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $statusId,
    ]);

    $studentSemester = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $semesterTwoId)
        ->firstOrFail();

    $studentSemester->update(['student_enrolment_status_id' => $statusId]);

    $user = User::factory()->create(['tenant_id' => $studentApplication->tenant_id]);
    $user->givePermissionTo(['view:students', 'viewAny:students', 'update:students']);

    return [
        'student' => $studentApplication->student,
        'enrolment' => $enrolment,
        'studentSemester' => $studentSemester->fresh(),
        'user' => $user,
    ];
}

it('updates a student semester status from unknown to active', function (): void {
    $context = createEnrolmentStatusUpdateContext('STATUS-PATCH-ACTIVE');
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $this->actingAs($context['user'])
        ->from(route('students.show', $context['student']))
        ->patch(route('students.student-semesters.status.update', [
            'student' => $context['student']->id,
            'student_semester' => $context['studentSemester']->id,
        ]), [
            'status' => StudentEnrolmentProgressionService::STATUS_ACTIVE,
        ])
        ->assertRedirect(route('students.show', $context['student']))
        ->assertSessionHas('success', __('students.enrolment_status_updated'));

    expect((int) $context['studentSemester']->fresh()?->student_enrolment_status_id)->toBe($activeId)
        ->and((int) $context['enrolment']->fresh()?->student_enrolment_status_id)->toBe($activeId);
});

it('returns the domain exception message when award is applied too early', function (): void {
    $context = createEnrolmentStatusUpdateContext('STATUS-PATCH-AWARD-EARLY', 'semester-1');

    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $studentSemester = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->where('semester_id', $semesterOneId)
        ->firstOrFail();

    $this->actingAs($context['user'])
        ->from(route('students.show', $context['student']))
        ->patch(route('students.student-semesters.status.update', [
            'student' => $context['student']->id,
            'student_semester' => $studentSemester->id,
        ]), [
            'status' => StudentEnrolmentProgressionService::STATUS_AWARD,
        ])
        ->assertRedirect(route('students.show', $context['student']))
        ->assertSessionHasErrors([
            'status' => __('students.enrolment_cannot_complete_level'),
        ]);
});
