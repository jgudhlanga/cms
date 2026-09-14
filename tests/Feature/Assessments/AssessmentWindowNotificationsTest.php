<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Assessments\AssessmentWindowEventEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendarNotificationDispatch;
use App\Models\Institution\AssessmentCalendar\AssessmentWindowNotification;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Notifications\Assessments\AssessmentWindowClosedNotification;
use App\Notifications\Assessments\AssessmentWindowOpenedNotification;
use App\Notifications\Assessments\DepartmentAssessmentCalendarPublishedNotification;
use App\Notifications\Assessments\GlobalAssessmentCalendarChangedNotification;
use App\Notifications\Assessments\MissingMarksNotification;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * @return array<string, mixed>
 */
function windowNotificationContext(string $startDate, string $endDate): array
{
    $context = createCourseWorkJsonApiContext();
    [$lecturer, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);

    [$hod, $hodStaff] = createLecturerUserWithStaff($context);
    $hodStaff->institutionDepartments()->syncWithoutDetaching([$context['institutionDepartment']->id]);
    $hod->syncRoles([Role::query()->where('name', RoleEnum::HEAD_OF_DEPARTMENT->name())->firstOrFail()]);

    $vp = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    $vp->assignRole(Role::query()->where('name', RoleEnum::VICE_PRINCIPAL->name())->firstOrFail());

    $globalCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'first_notification_days_before' => 10,
        'second_notification_days_before' => 5,
        'due_notification_days_before' => 0,
    ]);

    return [...$context, 'lecturer' => $lecturer, 'hod' => $hod->fresh(), 'vp' => $vp, 'globalCalendar' => $globalCalendar];
}

function runWindowNotifications(): void
{
    test()->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
}

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    Notification::fake();
});

test('a reminder whose date was missed is caught up on the next run', function () {
    // First (end - 10) and second (end - 5) reminder dates have both passed without a run.
    $context = windowNotificationContext(now()->subDays(20)->toDateString(), now()->addDays(3)->toDateString());

    runWindowNotifications();

    Notification::assertSentTo(
        $context['lecturer'],
        MissingMarksNotification::class,
        fn (MissingMarksNotification $notification): bool => $notification->tier === MissingMarksNotificationTierEnum::Second,
    );
    Notification::assertSentTo($context['hod'], MissingMarksNotification::class);
    Notification::assertSentTo($context['vp'], MissingMarksNotification::class);

    expect(AssessmentCalendarNotificationDispatch::query()
        ->where('scope_key', AssessmentCalendarNotificationDispatch::departmentScope((int) $context['institutionDepartment']->id))
        ->where('tier', MissingMarksNotificationTierEnum::Second->value)
        ->exists())->toBeTrue();
});

test('department windows drive lecturer and hod reminders on the department dates', function () {
    $context = windowNotificationContext(now()->subDays(20)->toDateString(), now()->addDays(30)->toDateString());

    DepartmentAssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_calendar_id' => $context['globalCalendar']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
    ]);

    runWindowNotifications();

    Notification::assertSentTo(
        $context['lecturer'],
        MissingMarksNotification::class,
        fn (MissingMarksNotification $notification): bool => $notification->tier === MissingMarksNotificationTierEnum::Second
            && $notification->effectiveEndDate === now()->addDays(5)->toDateString(),
    );
    Notification::assertSentTo($context['hod'], MissingMarksNotification::class);
    // The college window is still a month away from its first reminder, so VP Academics hears nothing yet.
    Notification::assertNotSentTo($context['vp'], MissingMarksNotification::class);
});

test('lecturers are told once when their capture window opens', function () {
    $context = windowNotificationContext(now()->subDay()->toDateString(), now()->addDays(40)->toDateString());

    runWindowNotifications();
    runWindowNotifications();

    Notification::assertSentToTimes($context['lecturer'], AssessmentWindowOpenedNotification::class, 1);

    expect(AssessmentWindowNotification::query()->where('event', AssessmentWindowEventEnum::Opened->value)->count())->toBe(1);
});

test('lecturers and the hod are told once when a window closes with marks missing', function () {
    $context = windowNotificationContext(now()->subDays(20)->toDateString(), now()->subDays(2)->toDateString());

    runWindowNotifications();
    runWindowNotifications();

    Notification::assertSentToTimes($context['lecturer'], AssessmentWindowClosedNotification::class, 1);
    Notification::assertSentTo(
        $context['hod'],
        AssessmentWindowClosedNotification::class,
        fn (AssessmentWindowClosedNotification $notification): bool => $notification->forLeadership && $notification->incompleteCount > 0,
    );
});

test('no closed notice is sent once the grace period has passed', function () {
    windowNotificationContext(now()->subDays(40)->toDateString(), now()->subDays(10)->toDateString());

    runWindowNotifications();

    Notification::assertNothingSentTo(User::query()->get(), AssessmentWindowClosedNotification::class);
});

test('lecturers with marks to capture are told when the hod sets a department window', function () {
    $context = windowNotificationContext(now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());

    $this->actingAs($context['hod'])
        ->post(route('department-assessment-calendars.store', ['department' => $context['institutionDepartment']->id]), [
            'assessment_calendar_id' => $context['globalCalendar']->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($context['lecturer'], DepartmentAssessmentCalendarPublishedNotification::class);
});

test('heads of department are told when a college change moves their department dates', function () {
    $context = windowNotificationContext(now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());

    DepartmentAssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_calendar_id' => $context['globalCalendar']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(25)->toDateString(),
    ]);

    grantCourseWorkLifecyclePermissions($context['vp'], ['update:assessment-calendar']);

    $this->actingAs($context['vp']->fresh())
        ->put(route('assessment-calendars.update', [
            'assessment_type' => $context['assessmentType']->id,
            'calendar' => $context['globalCalendar']->id,
        ]), [
            'academic_calendar_id' => $context['calendar']->id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertSuccessful();

    Notification::assertSentTo($context['hod'], GlobalAssessmentCalendarChangedNotification::class);
});
