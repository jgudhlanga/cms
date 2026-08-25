<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Students\StudentEnrolment;
use App\Services\Students\StudentPortalDashboardService;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
});

test('student portal dashboard notices include unpublished assessment type marks in a notification window', function () {
    $context = createCourseWorkJsonApiContext();
    $enrolment = StudentEnrolment::query()->findOrFail($context['studentEnrolment']->id);
    $student = $enrolment->student()->firstOrFail();
    $portalUser = $student->user()->firstOrFail();

    Permission::findOrCreate('viewOwnDashboard:students', 'web');
    Permission::findOrCreate('manageOwnStudentApplicationDetails:students', 'web');
    $portalUser->givePermissionTo(['viewOwnDashboard:students', 'manageOwnStudentApplicationDetails:students']);

    AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $enrolment->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'first_notification_days_before' => 10,
        'second_notification_days_before' => 5,
        'due_notification_days_before' => 0,
    ]);

    $payload = app(StudentPortalDashboardService::class)->build($portalUser);

    expect($payload['notices'])->not->toBeEmpty()
        ->and($payload['notices'][0]['message'])->toContain($context['module']->title)
        ->and($payload['notices'][0]['message'])->toContain($context['assessmentType']->name)
        ->and($payload['notices'][0]['message'])->not->toContain('lecturer');
});

test('student portal dashboard notices stay empty outside the notification window', function () {
    $context = createCourseWorkJsonApiContext();
    $enrolment = StudentEnrolment::query()->findOrFail($context['studentEnrolment']->id);
    $student = $enrolment->student()->firstOrFail();
    $portalUser = $student->user()->firstOrFail();

    Permission::findOrCreate('viewOwnDashboard:students', 'web');
    Permission::findOrCreate('manageOwnStudentApplicationDetails:students', 'web');
    $portalUser->givePermissionTo(['viewOwnDashboard:students', 'manageOwnStudentApplicationDetails:students']);

    AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $enrolment->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->addDays(20)->toDateString(),
        'end_date' => now()->addDays(40)->toDateString(),
        'first_notification_days_before' => 10,
        'second_notification_days_before' => 5,
        'due_notification_days_before' => 0,
    ]);

    $payload = app(StudentPortalDashboardService::class)->build($portalUser);

    expect($payload['notices'])->toBe([]);
});
