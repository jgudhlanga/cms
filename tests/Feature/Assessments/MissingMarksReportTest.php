<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Assessments\MissingMarksEscalation;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Notifications\Assessments\MissingMarksNotification;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    Notification::fake();
});

function createMissingMarksReportContext(): array
{
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    $vp = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Vice',
        'last_name' => 'Principal',
    ]);
    $vp->assignRole(Role::query()->where('name', RoleEnum::VICE_PRINCIPAL->name())->firstOrFail());
    $vp->givePermissionTo([
        'view:missing-marks-report',
        'export:missing-marks-report',
        'escalate:missing-marks',
        'remind:missing-marks',
    ]);

    $principal = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Principal',
        'last_name' => 'User',
    ]);
    $principal->assignRole(Role::query()->where('name', RoleEnum::PRINCIPAL->name())->firstOrFail());

    return [
        ...$context,
        'lecturerUser' => $lecturerUser,
        'staff' => $staff,
        'calendar' => $calendar,
        'vp' => $vp,
        'principal' => $principal,
    ];
}

test('vice principal can view the missing marks report', function () {
    $context = createMissingMarksReportContext();

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index', [
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'assessment_type_id' => $context['assessmentType']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/assessments/MissingMarksReport')
            ->has('rows', 1)
            ->where('rows.0.className', $context['academicCalendarClass']->name)
            ->where('rows.0.incompleteCount', 1)
        );
});

test('lecturer cannot view the missing marks report', function () {
    $context = createMissingMarksReportContext();

    $this->actingAs($context['lecturerUser'])
        ->get(route('missing-marks-report.index'))
        ->assertForbidden();
});

test('report is empty when all marks are captured', function () {
    $context = createMissingMarksReportContext();

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 40,
    ]);

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index', [
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'assessment_type_id' => $context['assessmentType']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/assessments/MissingMarksReport')
            ->has('rows', 0)
        );
});

test('escalate notifies the principal once', function () {
    $context = createMissingMarksReportContext();

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), [
            'assessment_calendar_id' => $context['calendar']->id,
            'notes' => 'Please follow up',
        ])
        ->assertRedirect();

    Notification::assertSentTo($context['principal'], MissingMarksNotification::class);
    expect(MissingMarksEscalation::query()->where('assessment_calendar_id', $context['calendar']->id)->exists())->toBeTrue();

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), [
            'assessment_calendar_id' => $context['calendar']->id,
            'notes' => 'Again',
        ])
        ->assertRedirect();

    Notification::assertSentToTimes($context['principal'], MissingMarksNotification::class, 1);
});

test('remind lecturers does not write a scheduled dispatch log', function () {
    $context = createMissingMarksReportContext();

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.remind'), [
            'assessment_calendar_id' => $context['calendar']->id,
        ])
        ->assertRedirect();

    Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
    $this->assertDatabaseMissing('assessment_calendar_notification_dispatches', [
        'assessment_calendar_id' => $context['calendar']->id,
    ]);
});
