<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendarNotificationDispatch;
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

function createMissingMarksCalendarContext(int $daysUntilDue = 10): array
{
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);

    $endDate = now()->startOfDay()->addDays($daysUntilDue);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => $endDate->toDateString(),
        'first_notification_days_before' => 10,
        'second_notification_days_before' => 5,
        'due_notification_days_before' => 0,
    ]);

    $vp = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Vice',
        'last_name' => 'Principal',
    ]);
    $vp->assignRole(Role::query()->where('name', RoleEnum::VICE_PRINCIPAL->name())->firstOrFail());

    return [
        ...$context,
        'lecturerUser' => $lecturerUser,
        'staff' => $staff,
        'calendar' => $calendar,
        'vp' => $vp,
    ];
}

test('sends the first reminder to lecturers when ten days remain', function () {
    $context = createMissingMarksCalendarContext(10);

    $this->artisan('assessment-calendars:send-missing-marks-notifications')
        ->assertSuccessful();

    Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
    Notification::assertNotSentTo($context['vp'], MissingMarksNotification::class);

    expect(AssessmentCalendarNotificationDispatch::query()->where('tier', MissingMarksNotificationTierEnum::First)->exists())->toBeTrue();
});

test('sends the second reminder to lecturers and vice principal academics', function () {
    $context = createMissingMarksCalendarContext(5);

    $this->artisan('assessment-calendars:send-missing-marks-notifications')
        ->assertSuccessful();

    Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
    Notification::assertSentTo($context['vp'], MissingMarksNotification::class);
});

test('sends the due reminder only to vice principal academics', function () {
    $context = createMissingMarksCalendarContext(0);

    $this->artisan('assessment-calendars:send-missing-marks-notifications')
        ->assertSuccessful();

    Notification::assertSentTo($context['vp'], MissingMarksNotification::class);
    Notification::assertNotSentTo($context['lecturerUser'], MissingMarksNotification::class);
});

test('does not send when all marks are captured', function () {
    $context = createMissingMarksCalendarContext(10);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 40,
    ]);

    $this->artisan('assessment-calendars:send-missing-marks-notifications')
        ->assertSuccessful();

    Notification::assertNothingSent();
});

test('does not send the same tier twice', function () {
    $context = createMissingMarksCalendarContext(10);

    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();

    Notification::assertSentToTimes($context['lecturerUser'], MissingMarksNotification::class, 1);
});
