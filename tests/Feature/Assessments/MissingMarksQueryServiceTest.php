<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Services\Assessments\MissingMarksQueryService;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
});

test('detects missing marks when no row exists for the assessment type', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    $rows = app(MissingMarksQueryService::class)->forCalendar($calendar);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['studentEnrolmentId'])->toBe($context['studentEnrolment']->id)
        ->and($rows[0]['moduleId'])->toBe($context['module']->id)
        ->and($rows[0]['lecturerUserIds'])->toContain($lecturerUser->id);
});

test('ignores students who already have a mark for the assessment type', function () {
    $context = createCourseWorkJsonApiContext();
    [, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 55,
    ]);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    expect(app(MissingMarksQueryService::class)->forCalendar($calendar))->toBe([]);
});

test('treats a null mark as missing', function () {
    $context = createCourseWorkJsonApiContext();

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => null,
    ]);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    expect(app(MissingMarksQueryService::class)->forCalendar($calendar))->toHaveCount(1);
});

test('excludes capture-mark-only modules from type-specific missing marks', function () {
    $context = createCourseWorkJsonApiContext();
    $context['module']->update(['capture_mark_only' => true]);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    expect(app(MissingMarksQueryService::class)->forCalendar($calendar))->toBe([]);
});

test('groups missing rows by class and module', function () {
    $context = createCourseWorkJsonApiContext();
    [, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);

    $calendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    $service = app(MissingMarksQueryService::class);
    $grouped = $service->groupedByClassModule($service->forCalendar($calendar));

    expect($grouped)->toHaveCount(1)
        ->and($grouped[0]['incompleteCount'])->toBe(1)
        ->and($grouped[0]['moduleId'])->toBe($context['module']->id);
});
