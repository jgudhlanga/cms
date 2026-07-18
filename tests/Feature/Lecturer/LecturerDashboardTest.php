<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Acl\RoleEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Acl\Permission;
use App\Models\Acl\Role;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    enableDashboardModule();
    seedDashboardAcademicCalendar();
});

function createLecturerUserWithStaff(array $context): array
{
    $lecturerUser = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Ada',
        'last_name' => 'Lecturer',
    ]);
    $lecturerUser->assignRole(Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail());

    foreach ([
        'view:lecturer-dashboard',
        'view:lecturer-classes',
        'view:lecturer-modules',
        'viewAny:course-work',
        'view:course-work',
        'update:course-work',
        'import:course-work',
        'view:academic-calendars',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $lecturerUser->givePermissionTo($permission);
    }

    $staff = Staff::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $lecturerUser->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Ms'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Female'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'employment_type_id' => EmploymentType::query()->firstOrCreate([
            'name' => EmploymentTypeEnum::FULL_TIME->value,
        ], [
            'description' => EmploymentTypeEnum::FULL_TIME->description(),
        ])->id,
        'employee_number' => 'LECT-PORTAL-'.uniqid(),
    ]);

    return [$lecturerUser, $staff];
}

function assignLecturerToClassModule(array $context, Staff $staff, bool $asTutor = true): void
{
    DB::table('course_syllabus_module_lecturers')->insert([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'staff_id' => $staff->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if (! $asTutor) {
        return;
    }

    $lecturerTypeId = ClassMetaDataType::query()
        ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
        ->value('id');

    AcademicCalendarClassMetaData::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'staff_id' => $staff->id,
        'class_metadata_type_id' => $lecturerTypeId,
    ]);
}

function prepareLecturerCalendar(array $context): int
{
    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => now()->subDays(10)->toDateString(),
        'closing_date' => now()->addMonths(3)->toDateString(),
    ]);
    seedDashboardIntakePeriod($context['tenant']->id);

    return $calendarId;
}

test('unauthorized users cannot access academic dashboard tab', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('lecturer-only user sees academic tab with teaching metrics and null attendance', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $context['assessmentType']->update(['weight_percent' => 100]);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 80,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->where('teachingDashboard.attendance', null)
            ->where('teachingDashboard.summary.passRate', 100)
            ->where('teachingDashboard.summary.averageMark', 60)
            ->where('teachingDashboard.summary.modulesCount', 1)
            ->has('teachingDashboard.topPerformingStudents', 1)
            ->where('overviewDashboard', null)
            ->where('academicDashboard', null)
        );
});

test('teaching dashboard shows null metrics when no marks exist', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->where('teachingDashboard.attendance', null)
            ->where('teachingDashboard.summary.passRate', null)
            ->where('teachingDashboard.summary.averageMark', null)
            ->where('teachingDashboard.summary.missingCourseWorkCount', 1)
            ->has('teachingDashboard.missingCourseWork', 1)
        );
});

test('dual permission user sees single academic tab with both dashboards', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    Permission::findOrCreate('view-academic:dashboards', 'web');
    $lecturerUser->givePermissionTo('view-academic:dashboards');

    enableDashboardModule([
        'overview' => false,
        'academic' => true,
        'enrolments' => false,
        'attendance' => false,
        'staff' => false,
        'finance' => false,
        'hostel' => false,
    ]);

    $this->actingAs($lecturerUser)
        ->get(dashboardUrlFor($lecturerUser, $calendarId))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Index')
            ->where('visibleTabs', ['academic'])
            ->where('teachingDashboard', fn ($value) => $value !== null)
            ->where('academicDashboard', fn ($value) => $value !== null)
        );
});

test('teaching classes index only returns assigned classes', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff);
    $calendarId = prepareLecturerCalendar($context);

    $otherClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $context['tenant']->id,
        'class_config_id' => $context['academicCalendarClass']->class_config_id,
        'name' => 'OTHER-CLASS-1',
        'description' => null,
    ]);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.classes.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/classes/Index')
            ->has('classes', 1)
            ->where('classes.0.id', $context['academicCalendarClass']->id)
            ->where('classes.0.name', $context['academicCalendarClass']->name)
            ->where('classes.0.id', fn ($id) => (int) $id !== (int) $otherClass->id)
        );
});

test('teaching modules index only returns assigned modules', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
    assignLecturerToClassModule($context, $staff, asTutor: false);
    $calendarId = prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get(route('teaching.modules.index', ['academic_calendar_id' => $calendarId]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('teaching/modules/Index')
            ->has('modules', 1)
            ->where('modules.0.id', $context['module']->id)
            ->where('modules.0.title', $context['module']->title)
        );
});

test('lecturer role seeder receives lecturer portal permissions', function () {
    $role = Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail();

    expect($role->hasPermissionTo('view:lecturer-dashboard'))->toBeTrue()
        ->and($role->hasPermissionTo('view:lecturer-classes'))->toBeTrue()
        ->and($role->hasPermissionTo('view:lecturer-modules'))->toBeTrue()
        ->and($role->hasPermissionTo('view:course-work'))->toBeTrue();
});

test('legacy lecturer dashboard url redirects to shared dashboard', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser] = createLecturerUserWithStaff($context);

    $this->actingAs($lecturerUser)
        ->get('/lecturer/dashboard')
        ->assertRedirect('/dashboard');
});

test('home redirects staff to shared dashboard', function () {
    $context = createCourseWorkJsonApiContext();
    [$lecturerUser] = createLecturerUserWithStaff($context);
    prepareLecturerCalendar($context);

    $this->actingAs($lecturerUser)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
