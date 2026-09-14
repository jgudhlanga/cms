<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Testing\TestResponse;

/**
 * @param  array<string, mixed>  $context
 */
function captureWindowCalendar(array $context, string $startDate, string $endDate): AssessmentCalendar
{
    return AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

/**
 * @param  array<string, mixed>  $context
 */
function captureWindowStoreMark(array $context, int $mark = 65, bool $markOnly = false): TestResponse
{
    $attributes = [
        'studentEnrolmentId' => $context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $context['module']->id,
        'mark' => $mark,
    ];

    if (! $markOnly) {
        $attributes['assessmentTypeId'] = $context['assessmentType']->id;
    }

    return jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, $attributes);
}

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();
    config(['coursework.require_assessment_calendar' => true]);
    $this->context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
    prepareLecturerCalendar($this->context);
});

test('capture is rejected when no assessment calendar has been set for the assessment type', function () {
    captureWindowStoreMark($this->context)->assertStatus(422);

    expect(CourseWorkMark::query()->count())->toBe(0);
});

test('capture is rejected before the window opens and accepted once it opens', function () {
    captureWindowCalendar(
        $this->context,
        now()->addDays(2)->toDateString(),
        now()->addDays(10)->toDateString(),
    );

    $early = captureWindowStoreMark($this->context);
    $early->assertStatus(422);
    expect(json_encode($early->json()))->toContain('capture opens on');

    $this->travel(3)->days();

    captureWindowStoreMark($this->context)->assertCreated();
});

test('capture after the due date is rejected with a due date passed message', function () {
    captureWindowCalendar(
        $this->context,
        now()->subDays(20)->toDateString(),
        now()->subDay()->toDateString(),
    );

    $response = captureWindowStoreMark($this->context);

    $response->assertStatus(422);
    expect(json_encode($response->json()))->toContain('Due date passed');
});

test('a department calendar that closes before the global calendar blocks capture for that department', function () {
    $globalCalendar = captureWindowCalendar(
        $this->context,
        now()->subDays(20)->toDateString(),
        now()->addDays(20)->toDateString(),
    );

    DepartmentAssessmentCalendar::query()->create([
        'tenant_id' => $this->context['tenant']->id,
        'assessment_calendar_id' => $globalCalendar->id,
        'institution_department_id' => $this->context['institutionDepartment']->id,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    captureWindowStoreMark($this->context)->assertStatus(422);
});

test('an approved extension lets the assigned lecturer capture after the due date until it expires', function () {
    captureWindowCalendar(
        $this->context,
        now()->subDays(20)->toDateString(),
        now()->subDay()->toDateString(),
    );

    CourseWorkCaptureExtension::query()->create([
        'tenant_id' => $this->context['tenant']->id,
        'academic_calendar_class_id' => $this->context['academicCalendarClass']->id,
        'course_syllabus_module_id' => $this->context['module']->id,
        'assessment_type_id' => $this->context['assessmentType']->id,
        'institution_department_id' => $this->context['institutionDepartment']->id,
        'requested_by' => $this->context['lecturerUser']->id,
        'reason' => 'Practical results were delayed by a laboratory outage.',
        'requested_until' => now()->addDays(2)->toDateString(),
        'status' => CourseWorkExtensionStatusEnum::Approved->value,
        'decided_by' => $this->context['admin']->id,
        'approved_until' => now()->addDays(2)->toDateString(),
        'decided_at' => now(),
    ]);

    captureWindowStoreMark($this->context)->assertCreated();

    $this->travel(3)->days();

    captureWindowStoreMark($this->context, 70)->assertStatus(422);
    expect((int) CourseWorkMark::query()->value('mark'))->toBe(65);
});

test('mark-only modules follow the assessment windows of the class mode of study', function () {
    $this->context['module']->update(['capture_mark_only' => true]);

    captureWindowCalendar(
        $this->context,
        now()->subDays(20)->toDateString(),
        now()->subDay()->toDateString(),
    );

    captureWindowStoreMark($this->context, 55, markOnly: true)->assertStatus(422);
});

test('the teaching class page exposes window status text for each assessment', function () {
    captureWindowCalendar(
        $this->context,
        now()->subDays(20)->toDateString(),
        now()->subDay()->toDateString(),
    );

    $this->actingAs($this->context['lecturerUser'])
        ->get(route('teaching.classes.show', $this->context['academicCalendarClass']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('classDetail.modules.0.courseWorkLock.allAssessmentTypesLocked', true)
            ->where('classDetail.modules.0.courseWorkLock.windows.0.status', 'closed')
            ->where(
                'classDetail.modules.0.courseWorkLock.windows.0.message',
                fn (string $message): bool => str_contains($message, 'Due date passed'),
            ));
});
