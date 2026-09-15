<?php

use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\ClassConfigLecturerInCharge;
use App\Models\AcademicCalendars\CourseWorkProgressReport;
use App\Models\Institution\Staff;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Notifications\Assessments\CourseWorkProgressReportAcknowledgedNotification;
use App\Notifications\Assessments\CourseWorkProgressReportSubmittedNotification;
use App\Services\AcademicCalendars\LecturerInChargeService;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * @param  array<string, mixed>  $context
 * @return array{0: User, 1: Staff}
 */
function progressDepartmentStaff(array $context, RoleEnum $role): array
{
    [$user, $staff] = createLecturerUserWithStaff($context);
    $staff->institutionDepartments()->syncWithoutDetaching([$context['institutionDepartment']->id]);
    $user->syncRoles([Role::query()->where('name', $role->name())->firstOrFail()]);

    return [$user->fresh(), $staff];
}

/**
 * @param  array<string, mixed>  $context
 */
function assignLecturerInChargeViaHttp(array $context, User $actor, ?int $staffId): TestResponse
{
    $classConfig = $context['classConfig'];

    return test()->actingAs($actor)->patch(route('academic-calendars.department-classes.lecturer-in-charge', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $classConfig->calendar_year,
        'class_config' => $classConfig->id,
    ]), ['staff_id' => $staffId]);
}

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    Notification::fake();

    $this->context = createCourseWorkJsonApiContext();
    [$this->moduleLecturer, $moduleLecturerStaff] = createLecturerUserWithStaff($this->context);
    assignLecturerToClassModule($this->context, $moduleLecturerStaff);

    [$this->hod] = progressDepartmentStaff($this->context, RoleEnum::HEAD_OF_DEPARTMENT);
    [$this->lecturerInCharge, $this->lecturerInChargeStaff] = progressDepartmentStaff($this->context, RoleEnum::LECTURER_IN_CHARGE);
});

test('the hod assigns a lecturer in charge from their own department', function () {
    assignLecturerInChargeViaHttp($this->context, $this->hod, (int) $this->lecturerInChargeStaff->id)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(ClassConfigLecturerInCharge::query()->where('class_config_id', $this->context['classConfig']->id)->value('staff_id'))
        ->toBe($this->lecturerInChargeStaff->id);

    assignLecturerInChargeViaHttp($this->context, $this->hod, null)->assertSessionHasNoErrors();

    expect(ClassConfigLecturerInCharge::query()->count())->toBe(0);
});

test('staff outside the department cannot be made lecturer in charge', function () {
    [, $outsiderStaff] = createLecturerUserWithStaff($this->context);

    assignLecturerInChargeViaHttp($this->context, $this->hod, (int) $outsiderStaff->id)
        ->assertSessionHasErrors('staff_id');

    expect(ClassConfigLecturerInCharge::query()->count())->toBe(0);
});

test('lecturers cannot assign a lecturer in charge', function () {
    assignLecturerInChargeViaHttp($this->context, $this->moduleLecturer, (int) $this->lecturerInChargeStaff->id)
        ->assertForbidden();
});

test('the lecturer in charge follows progress and can view but not capture marks', function () {
    app(LecturerInChargeService::class)->assign($this->context['classConfig'], (int) $this->lecturerInChargeStaff->id, $this->hod);

    $this->actingAs($this->lecturerInCharge)
        ->get(route('teaching.course-work-progress.show', ['class_config' => $this->context['classConfig']->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/courseWorkProgress/Show')
            ->where('canSubmitReport', true)
            ->where('progress.totals.expected', 1)
            ->where('progress.totals.missing', 1)
            ->where('progress.lecturerInCharge.staffId', $this->lecturerInChargeStaff->id));

    Sanctum::actingAs($this->lecturerInCharge);

    $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => ['academicCalendarClass' => $this->context['academicCalendarClass']->id],
        ]))
        ->assertSuccessful();

    jsonApiStoreCourseWorkMark($this->lecturerInCharge, $this->context, [
        'studentEnrolmentId' => $this->context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $this->context['module']->id,
        'assessmentTypeId' => $this->context['assessmentType']->id,
        'mark' => 71,
    ])->assertForbidden();
});

test('the lecturer in charge reports progress and the hod acknowledges it once', function () {
    app(LecturerInChargeService::class)->assign($this->context['classConfig'], (int) $this->lecturerInChargeStaff->id, $this->hod);

    $this->actingAs($this->lecturerInCharge)
        ->post(route('teaching.course-work-progress.reports.store', ['class_config' => $this->context['classConfig']->id]), [
            'notes' => 'Networking tests are still outstanding for one class.',
        ])
        ->assertSessionHasNoErrors();

    $report = CourseWorkProgressReport::query()->firstOrFail();

    expect($report->snapshot['totals']['expected'])->toBe(1)
        ->and($report->notes)->toContain('Networking');

    Notification::assertSentTo($this->hod, CourseWorkProgressReportSubmittedNotification::class);

    $this->actingAs($this->hod)
        ->get(route('course-work-progress-reports.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/courseWorkProgressReports/Index')
            ->where('reports.0.can.acknowledge', true));

    $this->actingAs($this->hod)
        ->post(route('course-work-progress-reports.acknowledge', ['course_work_progress_report' => $report->id]), [
            'comment' => 'Thanks — follow up with the module lecturer.',
        ])
        ->assertSessionHasNoErrors();

    expect($report->fresh()->acknowledged_by)->toBe($this->hod->id);
    Notification::assertSentTo($this->lecturerInCharge, CourseWorkProgressReportAcknowledgedNotification::class);

    $this->actingAs($this->hod)
        ->post(route('course-work-progress-reports.acknowledge', ['course_work_progress_report' => $report->id]))
        ->assertSessionHasErrors('report');
});

test('lecturers who are not in charge cannot see or report programme progress', function () {
    $this->actingAs($this->moduleLecturer)
        ->get(route('teaching.course-work-progress.show', ['class_config' => $this->context['classConfig']->id]))
        ->assertForbidden();

    $this->actingAs($this->lecturerInCharge)
        ->post(route('teaching.course-work-progress.reports.store', ['class_config' => $this->context['classConfig']->id]))
        ->assertForbidden();
});

test('progress opens on the current year and keeps earlier years for reference only', function () {
    $classConfig = $this->context['classConfig'];
    $currentYear = (int) $classConfig->calendar_year;
    $this->travelTo(now()->setDate($currentYear, 6, 15));

    $previousYearConfig = $classConfig->replicate();
    $previousYearConfig->calendar_year = (string) ($currentYear - 1);
    $previousYearConfig->save();

    $previousYearClass = $this->context['academicCalendarClass']->replicate();
    $previousYearClass->class_config_id = $previousYearConfig->id;
    $previousYearClass->save();

    $service = app(LecturerInChargeService::class);
    $service->assign($classConfig, (int) $this->lecturerInChargeStaff->id, $this->hod);
    $service->assign($previousYearConfig, (int) $this->lecturerInChargeStaff->id, $this->hod);

    $this->actingAs($this->lecturerInCharge)
        ->get(route('teaching.course-work-progress.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/courseWorkProgress/Index')
            ->where('calendarYear', $currentYear)
            ->where('availableYears', [$currentYear, $currentYear - 1])
            ->where('isHistorical', false)
            ->has('programmes', 1)
            ->where('programmes.0.classConfigId', $classConfig->id));

    $this->actingAs($this->lecturerInCharge)
        ->get(route('teaching.course-work-progress.index', ['calendar_year' => $currentYear - 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('calendarYear', $currentYear - 1)
            ->where('isHistorical', true)
            ->has('programmes', 1)
            ->where('programmes.0.classConfigId', $previousYearConfig->id));

    // A year the viewer has no programmes in falls back to the current year.
    $this->actingAs($this->lecturerInCharge)
        ->get(route('teaching.course-work-progress.index', ['calendar_year' => 1990]))
        ->assertInertia(fn ($page) => $page->where('calendarYear', $currentYear));

    // Earlier years are for reference: viewable, but no new progress reports.
    $this->actingAs($this->lecturerInCharge)
        ->get(route('teaching.course-work-progress.show', ['class_config' => $previousYearConfig->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('isHistorical', true)
            ->where('canSubmitReport', false));

    $this->actingAs($this->lecturerInCharge)
        ->post(route('teaching.course-work-progress.reports.store', ['class_config' => $previousYearConfig->id]))
        ->assertForbidden();

    expect(CourseWorkProgressReport::query()->count())->toBe(0);
});
