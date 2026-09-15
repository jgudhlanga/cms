<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use Database\Seeders\Rbac\RoleGroupSeeder;
use Database\Seeders\Rbac\RolesTableSeeder;

const DEPARTMENT_CALENDAR_HOD_PERMISSIONS = [
    'viewOnlyOwnDepartment:departments',
    'viewAny:department-assessment-calendar',
    'view:department-assessment-calendar',
    'create:department-assessment-calendar',
    'update:department-assessment-calendar',
    'delete:department-assessment-calendar',
    'restore:department-assessment-calendar',
];

/**
 * @param  array<string, mixed>  $context
 * @param  list<string>  $permissions
 */
function departmentCalendarUser(array $context, array $permissions = DEPARTMENT_CALENDAR_HOD_PERMISSIONS): User
{
    [$user, $staff] = createLecturerUserWithStaff($context);
    $staff->institutionDepartments()->syncWithoutDetaching([$context['institutionDepartment']->id]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user->fresh();
}

/**
 * @param  array<string, mixed>  $context
 */
function departmentCalendarGlobal(array $context, string $startDate, string $endDate): AssessmentCalendar
{
    return AssessmentCalendar::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'academic_calendar_id' => $context['calendar']->id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

/**
 * @param  array<string, mixed>  $context
 */
function departmentCalendarWindow(array $context, AssessmentCalendar $globalCalendar, string $startDate, string $endDate): DepartmentAssessmentCalendar
{
    return DepartmentAssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_calendar_id' => $globalCalendar->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

beforeEach(function () {
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();
    $this->context = createCourseWorkJsonApiContext();
});

test('hod can set a department window nested inside the global window', function () {
    $hod = departmentCalendarUser($this->context);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());

    $this->actingAs($hod)
        ->post(route('department-assessment-calendars.store', ['department' => $this->context['institutionDepartment']->id]), [
            'assessment_calendar_id' => $globalCalendar->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Submit practical marks early.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $departmentCalendar = DepartmentAssessmentCalendar::query()->firstOrFail();

    expect($departmentCalendar->institution_department_id)->toBe($this->context['institutionDepartment']->id)
        ->and($departmentCalendar->end_date->toDateString())->toBe(now()->addDays(10)->toDateString())
        ->and($departmentCalendar->created_by)->toBe($hod->id);
});

test('department window dates outside the global window are rejected', function () {
    $hod = departmentCalendarUser($this->context);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());

    $this->actingAs($hod)
        ->post(route('department-assessment-calendars.store', ['department' => $this->context['institutionDepartment']->id]), [
            'assessment_calendar_id' => $globalCalendar->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(40)->toDateString(),
        ])
        ->assertSessionHasErrors(['start_date', 'end_date']);

    expect(DepartmentAssessmentCalendar::query()->count())->toBe(0);
});

test('hod cannot manage another department assessment windows', function () {
    $hod = departmentCalendarUser($this->context);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());
    $otherDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $this->context['tenant']->id,
        'department_id' => Department::factory()->create(['name' => 'Other Department '.uniqid()])->id,
        'department_code' => 'OTHER-'.uniqid(),
    ]);

    $this->actingAs($hod)
        ->get(route('department-assessment-calendars.index', ['department' => $otherDepartment->id]))
        ->assertForbidden();

    $this->actingAs($hod)
        ->post(route('department-assessment-calendars.store', ['department' => $otherDepartment->id]), [
            'assessment_calendar_id' => $globalCalendar->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ])
        ->assertForbidden();
});

test('users who can only view department calendars cannot create them', function () {
    $viewer = departmentCalendarUser($this->context, ['viewAny:department-assessment-calendar', 'view:department-assessment-calendar']);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());

    $this->actingAs($viewer)
        ->get(route('department-assessment-calendars.index', ['department' => $this->context['institutionDepartment']->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/departments/assessment-calendars/Index')
            ->where('can.create', false)
            ->where('rows.0.globalCalendarId', $globalCalendar->id)
            ->where('rows.0.departmentCalendar', null));

    $this->actingAs($viewer)
        ->post(route('department-assessment-calendars.store', ['department' => $this->context['institutionDepartment']->id]), [
            'assessment_calendar_id' => $globalCalendar->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ])
        ->assertForbidden();
});

test('no department window can be set once the global window has closed', function () {
    $hod = departmentCalendarUser($this->context);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(20)->toDateString(), now()->subDay()->toDateString());

    $this->actingAs($hod)
        ->post(route('department-assessment-calendars.store', ['department' => $this->context['institutionDepartment']->id]), [
            'assessment_calendar_id' => $globalCalendar->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
        ])
        ->assertSessionHasErrors('assessment_calendar_id');
});

test('the start date of an opened department window cannot change', function () {
    $hod = departmentCalendarUser($this->context);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(10)->toDateString(), now()->addDays(30)->toDateString());
    $departmentCalendar = departmentCalendarWindow($this->context, $globalCalendar, now()->subDays(2)->toDateString(), now()->addDays(5)->toDateString());

    $route = route('department-assessment-calendars.update', [
        'department' => $this->context['institutionDepartment']->id,
        'department_assessment_calendar' => $departmentCalendar->id,
    ]);

    $this->actingAs($hod)
        ->put($route, [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ])
        ->assertSessionHasErrors('start_date');

    $this->actingAs($hod)
        ->put($route, [
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($departmentCalendar->fresh()->end_date->toDateString())->toBe(now()->addDays(8)->toDateString());
});

test('a closed department window cannot be changed or removed', function () {
    $hod = departmentCalendarUser($this->context);
    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(10)->toDateString(), now()->addDays(30)->toDateString());
    $departmentCalendar = departmentCalendarWindow($this->context, $globalCalendar, now()->subDays(10)->toDateString(), now()->subDay()->toDateString());
    $routeParameters = [
        'department' => $this->context['institutionDepartment']->id,
        'department_assessment_calendar' => $departmentCalendar->id,
    ];

    $this->actingAs($hod)
        ->put(route('department-assessment-calendars.update', $routeParameters), [
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ])
        ->assertSessionHasErrors('end_date');

    $this->actingAs($hod)
        ->delete(route('department-assessment-calendars.destroy', $routeParameters))
        ->assertSessionHas('error');

    expect($departmentCalendar->fresh()->deleted_at)->toBeNull()
        ->and($departmentCalendar->fresh()->end_date->toDateString())->toBe(now()->subDay()->toDateString());
});

test('moving the global window pulls department dates back inside it', function () {
    $vp = User::factory()->create(['tenant_id' => $this->context['tenant']->id]);
    Permission::findOrCreate('update:assessment-calendar', 'web');
    $vp->givePermissionTo('update:assessment-calendar');

    $globalCalendar = departmentCalendarGlobal($this->context, now()->subDays(5)->toDateString(), now()->addDays(30)->toDateString());
    $departmentCalendar = departmentCalendarWindow($this->context, $globalCalendar, now()->subDays(5)->toDateString(), now()->addDays(25)->toDateString());

    $this->actingAs($vp)
        ->put(route('assessment-calendars.update', [
            'assessment_type' => $this->context['assessmentType']->id,
            'calendar' => $globalCalendar->id,
        ]), [
            'academic_calendar_id' => $this->context['calendar']->id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        ])
        ->assertSuccessful();

    expect($departmentCalendar->fresh()->end_date->toDateString())->toBe(now()->addDays(10)->toDateString())
        ->and($departmentCalendar->fresh()->start_date->toDateString())->toBe(now()->subDays(5)->toDateString());
});
