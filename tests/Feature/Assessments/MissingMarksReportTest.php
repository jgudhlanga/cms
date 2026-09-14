<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Rbac\RoleEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Assessments\MissingMarksEscalation;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Rbac\Role;
use App\Models\Users\User;
use App\Notifications\Assessments\MissingMarksNotification;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // The course work test world is built for 2026; pin "today" inside that year.
    $this->travelTo(now()->setDate(2026, 6, 15));
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    Notification::fake();
});

function createMissingMarksReportContext(): array
{
    $context = createCourseWorkJsonApiContext();
    $context['institutionDepartment']->department->update(['is_academic' => true]);

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

test('vice principal can view and export the missing marks report', function () {
    $context = createMissingMarksReportContext();

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index', [
            'assessment_type_id' => $context['assessmentType']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/assessments/MissingMarksReport')
            ->where('filters.calendarYear', 2026)
            ->where('filters.assessmentTypeId', $context['assessmentType']->id)
            ->has('rows', 1)
            ->where('rows.0.className', $context['academicCalendarClass']->name)
            ->where('rows.0.courseName', $context['departmentCourse']->course->name)
            ->where('rows.0.incompleteCount', 1)
        );

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.export'))
        ->assertOk();
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
            'assessment_type_id' => $context['assessmentType']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/assessments/MissingMarksReport')
            ->has('rows', 0)
        );
});

test('filters follow the department set-up from level down to the module lecturers', function () {
    $context = createMissingMarksReportContext();
    $department = $context['institutionDepartment'];
    $classConfig = $context['classConfig']->fresh(['departmentLevel', 'departmentCourse']);
    $levelId = (int) $classConfig->departmentLevel->level_id;
    $courseId = (int) $classConfig->departmentCourse->course_id;

    // A level the department offers is listed even though nothing is missing there.
    DepartmentLevel::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $department->id,
        'level_id' => Level::factory()->create(['name' => 'ND '.uniqid()])->id,
    ]);

    $nonAcademicDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $context['tenant']->id,
        'department_id' => Department::factory()->create(['name' => 'Accounts '.uniqid(), 'is_academic' => false])->id,
        'department_code' => 'ACC-'.uniqid(),
        'description' => 'Non-academic department',
    ]);
    [, $unassignedLecturerStaff] = createLecturerUserWithStaff($context);

    $report = fn (array $filters = []) => $this->actingAs($context['vp'])->get(route('missing-marks-report.index', $filters));

    // The only academic department is chosen straight away, so its levels are listed; the rest wait their turn.
    $report()
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('filterOptions.departments', 1)
            ->where('filters.departmentId', $department->id)
            ->has('filterOptions.levels', 2)
            ->has('filterOptions.courses', 0)
            ->has('filterOptions.modules', 0)
            ->has('filterOptions.lecturers', 0)
            ->has('rows', 1)
        );

    $report(['institution_department_id' => $department->id, 'level_id' => $levelId])
        ->assertInertia(fn ($page) => $page
            ->where('filters.levelId', $levelId)
            ->has('filterOptions.courses', 1)
            ->where('filterOptions.courses.0.id', $courseId)
            ->has('filterOptions.modules', 0)
        );

    $report(['institution_department_id' => $department->id, 'level_id' => $levelId, 'course_id' => $courseId])
        ->assertInertia(fn ($page) => $page
            ->has('filterOptions.modules', 1)
            ->where('filterOptions.modules.0.id', $context['module']->id)
            ->has('filterOptions.lecturers', 0)
        );

    $report([
        'institution_department_id' => $department->id,
        'level_id' => $levelId,
        'course_id' => $courseId,
        'module_id' => $context['module']->id,
        'lecturer_staff_id' => $context['staff']->id,
    ])
        ->assertInertia(fn ($page) => $page
            ->has('filterOptions.lecturers', 1)
            ->where('filterOptions.lecturers.0.id', $context['staff']->id)
            ->where('filters.lecturerStaffId', $context['staff']->id)
            ->has('rows', 1)
        );

    // Choices that are not on offer are ignored: a non-academic department, an unknown level, a module without its
    // course, and a lecturer who is not on the module.
    $report([
        'institution_department_id' => $nonAcademicDepartment->id,
        'level_id' => 999999,
        'module_id' => $context['module']->id,
        'lecturer_staff_id' => $unassignedLecturerStaff->id,
    ])
        ->assertInertia(fn ($page) => $page
            ->where('filters.departmentId', $department->id)
            ->where('filters.levelId', null)
            ->where('filters.moduleId', null)
            ->where('filters.lecturerStaffId', null)
            ->has('rows', 1)
        );
});

test('earlier years are listed for reference without reminders or escalation', function () {
    $context = createMissingMarksReportContext();

    $previousAcademicCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    $previousCalendar = AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $previousAcademicCalendar->id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => '2025-03-01',
        'end_date' => '2025-04-01',
    ]);

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index'))
        ->assertInertia(fn ($page) => $page
            ->where('availableYears', [2026, 2025])
            ->where('isHistorical', false)
        );

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index', ['calendar_year' => 2025]))
        ->assertInertia(fn ($page) => $page
            ->where('filters.calendarYear', 2025)
            ->where('isHistorical', true)
            ->has('rows', 0)
        );

    // A year without assessment calendars falls back to the current year.
    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index', ['calendar_year' => 1990]))
        ->assertInertia(fn ($page) => $page->where('filters.calendarYear', 2026));

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), ['assessment_calendar_id' => $previousCalendar->id])
        ->assertSessionHasErrors('escalation');

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.remind'), ['assessment_calendar_id' => $previousCalendar->id])
        ->assertSessionHas('error');

    Notification::assertNothingSent();
});

test('escalate notifies the principal once with the note', function () {
    $context = createMissingMarksReportContext();

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), [
            'assessment_calendar_id' => $context['calendar']->id,
            'notes' => 'Please follow up',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertSentTo(
        $context['principal'],
        MissingMarksNotification::class,
        fn (MissingMarksNotification $notification): bool => $notification->escalationNotes === 'Please follow up',
    );
    expect(MissingMarksEscalation::query()->where('assessment_calendar_id', $context['calendar']->id)->exists())->toBeTrue();

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), [
            'assessment_calendar_id' => $context['calendar']->id,
            'notes' => 'Again',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('escalation');

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
