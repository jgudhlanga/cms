<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Jobs\Applications\ExportApplicationJob;
use App\Jobs\Enrolments\ExportStudentEnrollmentJob;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

require_once __DIR__.'/MaintenanceControllerTest.php';

function createFinalisedEnrolment(string $studentNumber, string $calendarYear = '2025/2026'): StudentEnrolment
{
    $application = createVerifiedStudentApplication($studentNumber);

    ClassList::query()
        ->where('student_application_id', $application->id)
        ->update(['type' => ClassListTypeEnum::FINAL->value]);

    $application->update(['workflow_step_id' => resolveWorkflowStep(WorkflowStepEnum::ENROLLED)->id]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => $calendarYear,
        'type' => 'semester',
        'opening_date' => now()->startOfYear()->toDateString(),
        'closing_date' => now()->endOfYear()->toDateString(),
    ]);

    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1'],
    );

    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'enrolled'],
        ['name' => 'Enrolled'],
    );

    return StudentEnrolment::query()->create([
        'student_enrolment_status_id' => $status->id,
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
    ]);
}

it('redirects guests from the enrolment export preview', function (): void {
    $this->get(route('maintenance.exports.student-enrollment.preview'))
        ->assertRedirect('/login');
});

it('forbids unauthorised users from the enrolment export preview', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('maintenance.exports.student-enrollment.preview'))
        ->assertForbidden();
});

it('previews finalised enrolments without filters', function (): void {
    actingAsRootMaintenanceUser();
    createFinalisedEnrolment('STU-PREVIEW-1');

    $this->get(route('maintenance.exports.student-enrollment.preview'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/StudentEnrollmentExport')
            ->where('stats.total', 1)
            ->has('enrolments.data', 1)
            ->has('calendarYears')
            ->has('semesters')
            ->has('calendarTypes'));
});

it('narrows the enrolment preview by department', function (): void {
    actingAsRootMaintenanceUser();
    $enrolment = createFinalisedEnrolment('STU-PREVIEW-2');
    createFinalisedEnrolment('STU-PREVIEW-3');

    $this->get(route('maintenance.exports.student-enrollment.preview', [
        'department' => [$enrolment->institution_department_id],
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/StudentEnrollmentExport')
            ->where('stats.total', 1)
            ->has('enrolments.data', 1));
});

it('narrows the enrolment preview by calendar year, semester and calendar type', function (): void {
    actingAsRootMaintenanceUser();
    $enrolment = createFinalisedEnrolment('STU-PREVIEW-4', '2024/2025');
    createFinalisedEnrolment('STU-PREVIEW-5', '2025/2026');

    $this->get(route('maintenance.exports.student-enrollment.preview', [
        'calendar_year' => '2024/2025',
        'semester_id' => $enrolment->semester_id,
        'calendar_type' => 'semester',
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('stats.total', 1)
            ->has('enrolments.data', 1));

    $this->get(route('maintenance.exports.student-enrollment.preview', ['calendar_year' => '1999/2000']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('stats.total', 0));
});

it('queues the enrolment export with the selected filters', function (): void {
    Queue::fake();
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.exports.student-enrollment.preview'))
        ->post(route('maintenance.exports.student-enrollment'), [
            'calendar_year' => '2025/2026',
            'calendar_type' => 'semester',
            'department' => [7],
            'recipient_emails' => 'exports@example.test',
        ])
        ->assertSessionHas('success');

    Queue::assertPushed(ExportStudentEnrollmentJob::class, fn (ExportStudentEnrollmentJob $job): bool => $job->filters === [
        'department' => [7],
        'calendar_year' => '2025/2026',
        'calendar_type' => 'semester',
    ] && $job->recipientEmails === ['exports@example.test']);
});

it('previews applications and keeps one row per student', function (): void {
    actingAsRootMaintenanceUser();
    $application = createVerifiedStudentApplication('STU-APP-1');

    StudentApplication::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $application->student_id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'intake_period_id' => $application->intake_period_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'workflow_step_id' => $application->workflow_step_id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
    ]);

    $this->get(route('maintenance.exports.application.preview'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/ApplicationExport')
            ->where('stats.total', 1)
            ->has('applications.data', 1)
            ->has('intakePeriods'));
});

it('narrows the application preview by intake period and applied date range', function (): void {
    actingAsRootMaintenanceUser();
    $application = createVerifiedStudentApplication('STU-APP-2');

    $this->get(route('maintenance.exports.application.preview', [
        'intake_period_id' => $application->intake_period_id,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('stats.total', 1));

    $this->get(route('maintenance.exports.application.preview', [
        'applied_from' => now()->addWeek()->toDateString(),
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('stats.total', 0));
});

it('queues the application export with intake and date filters', function (): void {
    Queue::fake();
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.exports.application.preview'))
        ->post(route('maintenance.exports.application'), [
            'intake_period_id' => 3,
            'applied_from' => '2026-01-01',
            'applied_to' => '2026-06-30',
            'recipient_emails' => 'exports@example.test',
        ])
        ->assertSessionHas('success');

    Queue::assertPushed(ExportApplicationJob::class, fn (ExportApplicationJob $job): bool => $job->filters === [
        'intake_period_id' => 3,
        'applied_from' => '2026-01-01',
        'applied_to' => '2026-06-30',
    ]);
});

it('rejects an application export date range that ends before it starts', function (): void {
    actingAsRootMaintenanceUser();

    $this->from(route('maintenance.exports.application.preview'))
        ->post(route('maintenance.exports.application'), [
            'applied_from' => '2026-06-30',
            'applied_to' => '2026-01-01',
            'recipient_emails' => 'exports@example.test',
        ])
        ->assertSessionHasErrors('applied_to');
});
