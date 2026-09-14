<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Notifications\Assessments\CourseWorkExtensionDecidedNotification;
use App\Notifications\Assessments\CourseWorkExtensionRequestedNotification;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/**
 * @param  array<string, mixed>  $context
 */
function extensionTestHeadOfDepartment(array $context): User
{
    [$hod, $staff] = createLecturerUserWithStaff($context);
    $staff->institutionDepartments()->syncWithoutDetaching([$context['institutionDepartment']->id]);
    $hod->syncRoles([Role::query()->where('name', RoleEnum::HEAD_OF_DEPARTMENT->name())->firstOrFail()]);

    return $hod->fresh();
}

/**
 * @param  array<string, mixed>  $context
 * @param  array<string, mixed>  $overrides
 */
function extensionTestRequest(array $context, User $user, array $overrides = []): TestResponse
{
    return test()->actingAs($user)->post(route('teaching.classes.extensions.store', [
        'academic_calendar_class' => $context['academicCalendarClass']->id,
        'course_syllabus_module' => $context['module']->id,
    ]), [
        'assessment_type_id' => $context['assessmentType']->id,
        'requested_until' => now()->addDays(5)->toDateString(),
        'reason' => 'Practical assessment results were delayed by a laboratory outage.',
        ...$overrides,
    ]);
}

function extensionTestDecide(User $user, CourseWorkCaptureExtension $extension, string $action, array $payload = []): TestResponse
{
    return test()->actingAs($user)->post(
        route('course-work-extensions.'.$action, ['course_work_capture_extension' => $extension->id]),
        $payload,
    );
}

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();
    Notification::fake();
    config(['coursework.require_assessment_calendar' => true]);

    $this->context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
    prepareLecturerCalendar($this->context);

    // The college window stays open for ten more days, but the department closed its own window yesterday.
    $this->globalCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $this->context['tenant']->id,
        'assessment_type_id' => $this->context['assessmentType']->id,
        'academic_calendar_id' => $this->context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    DepartmentAssessmentCalendar::query()->create([
        'tenant_id' => $this->context['tenant']->id,
        'assessment_calendar_id' => $this->globalCalendar->id,
        'institution_department_id' => $this->context['institutionDepartment']->id,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    $this->hod = extensionTestHeadOfDepartment($this->context);
    $this->lecturer = $this->context['lecturerUser'];
});

test('the assigned lecturer can request an extension once the window has closed and the hod is notified', function () {
    extensionTestRequest($this->context, $this->lecturer)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $extension = CourseWorkCaptureExtension::query()->firstOrFail();

    expect($extension->status)->toBe(CourseWorkExtensionStatusEnum::Pending)
        ->and($extension->institution_department_id)->toBe($this->context['institutionDepartment']->id)
        ->and($extension->assessment_calendar_id)->toBe($this->globalCalendar->id);

    Notification::assertSentTo($this->hod, CourseWorkExtensionRequestedNotification::class);
    Notification::assertNotSentTo($this->lecturer, CourseWorkExtensionRequestedNotification::class);
});

test('extensions cannot be requested while the capture window is still open', function () {
    DepartmentAssessmentCalendar::query()->forceDelete();

    extensionTestRequest($this->context, $this->lecturer)->assertSessionHasErrors('assessment_type_id');

    expect(CourseWorkCaptureExtension::query()->count())->toBe(0);
});

test('lecturers not assigned to the module cannot request an extension', function () {
    [$otherLecturer] = createLecturerUserWithStaff($this->context);
    grantCourseWorkLifecyclePermissions($otherLecturer, ['request:course-work-extensions']);

    extensionTestRequest($this->context, $otherLecturer->fresh())->assertForbidden();
});

test('the hod approves within the college deadline and capture reopens for the lecturer', function () {
    extensionTestRequest($this->context, $this->lecturer);
    $extension = CourseWorkCaptureExtension::query()->firstOrFail();

    jsonApiStoreCourseWorkMark($this->lecturer, $this->context, [
        'studentEnrolmentId' => $this->context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $this->context['module']->id,
        'assessmentTypeId' => $this->context['assessmentType']->id,
        'mark' => 64,
    ])->assertStatus(422);

    extensionTestDecide($this->hod, $extension, 'approve', [
        'approved_until' => now()->addDays(5)->toDateString(),
        'note' => 'Approved because of the laboratory outage.',
    ])->assertSessionHasNoErrors();

    expect($extension->fresh()->status)->toBe(CourseWorkExtensionStatusEnum::Approved)
        ->and($extension->fresh()->decided_by)->toBe($this->hod->id);

    Notification::assertSentTo($this->lecturer, CourseWorkExtensionDecidedNotification::class);

    jsonApiStoreCourseWorkMark($this->lecturer, $this->context, [
        'studentEnrolmentId' => $this->context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $this->context['module']->id,
        'assessmentTypeId' => $this->context['assessmentType']->id,
        'mark' => 64,
    ])->assertCreated();
});

test('the hod cannot approve past the college deadline but vp academics can', function () {
    extensionTestRequest($this->context, $this->lecturer, ['requested_until' => now()->addDays(12)->toDateString()]);
    $extension = CourseWorkCaptureExtension::query()->firstOrFail();

    Notification::assertSentTo($this->context['vp'], CourseWorkExtensionRequestedNotification::class);

    extensionTestDecide($this->hod, $extension, 'approve', [
        'approved_until' => now()->addDays(15)->toDateString(),
    ])->assertSessionHasErrors('approved_until');

    expect($extension->fresh()->status)->toBe(CourseWorkExtensionStatusEnum::Pending);

    extensionTestDecide($this->context['vp']->fresh(), $extension, 'approve', [
        'approved_until' => now()->addDays(15)->toDateString(),
    ])->assertSessionHasNoErrors();

    expect($extension->fresh()->status)->toBe(CourseWorkExtensionStatusEnum::Approved)
        ->and($extension->fresh()->approved_until->toDateString())->toBe(now()->addDays(15)->toDateString());
});

test('nobody can approve their own extension request', function () {
    extensionTestRequest($this->context, $this->hod)->assertSessionHasNoErrors();
    $extension = CourseWorkCaptureExtension::query()->firstOrFail();

    extensionTestDecide($this->hod, $extension, 'approve', [
        'approved_until' => now()->addDays(5)->toDateString(),
    ])->assertForbidden();
});

test('revoking an approved extension closes capture again', function () {
    extensionTestRequest($this->context, $this->lecturer);
    $extension = CourseWorkCaptureExtension::query()->firstOrFail();
    extensionTestDecide($this->hod, $extension, 'approve', ['approved_until' => now()->addDays(5)->toDateString()]);

    extensionTestDecide($this->hod, $extension, 'revoke', ['note' => 'Marks were captured by the HOD.'])
        ->assertSessionHasNoErrors();

    expect($extension->fresh()->status)->toBe(CourseWorkExtensionStatusEnum::Revoked);

    jsonApiStoreCourseWorkMark($this->lecturer, $this->context, [
        'studentEnrolmentId' => $this->context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $this->context['module']->id,
        'assessmentTypeId' => $this->context['assessmentType']->id,
        'mark' => 64,
    ])->assertStatus(422);
});

test('the hod sees pending requests for their department in the decision queue', function () {
    extensionTestRequest($this->context, $this->lecturer);

    $this->actingAs($this->hod)
        ->get(route('course-work-extensions.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/courseWorkExtensions/Index')
            ->where('isApprover', true)
            ->where('extensions.0.status', 'pending')
            ->where('extensions.0.can.approve', true));

    $this->actingAs($this->lecturer)
        ->get(route('course-work-extensions.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('isApprover', false)
            ->where('extensions.0.can.approve', false)
            ->where('extensions.0.can.cancel', true));
});
