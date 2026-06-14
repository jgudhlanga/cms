<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Http\Requests\Institution\CourseSyllabusModuleRequest;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\Validator;

function makeSyllabusModuleContext(?AcademicCalendarTypeEnum $calendarType = null): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo([
        'create:course-syllabuses',
        'update:course-syllabuses',
        'create:course-syllabus-modules',
        'update:course-syllabus-modules',
        'viewAny:course-syllabus-modules',
        'view:course-syllabus-modules',
        'view:course-syllabuses',
    ]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'mod_test_'.uniqid(),
        'description' => 'Syllabus module test department',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $resolvedCalendarType = $calendarType ?? AcademicCalendarTypeEnum::SEMESTER;
    $level = Level::factory()->create([
        'name' => 'Level 1',
        'calendar_type' => $resolvedCalendarType,
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $courseSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Module Syllabus '.uniqid(),
        'code' => 'MS-'.uniqid(),
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $semesterOne = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );
    $termOne = AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'term-1'],
        ['name' => 'Term 1', 'description' => null],
    );

    return compact(
        'tenant',
        'user',
        'institutionDepartment',
        'courseSyllabus',
        'semesterOne',
        'semesterTwo',
        'termOne',
    );
}

it('validates required syllabus course module fields', function () {
    $request = new CourseSyllabusModuleRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('course_syllabus_id'))->toBeTrue();
    expect($validator->errors()->has('academic_year_option_id'))->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
    expect($validator->errors()->has('code'))->toBeTrue();
});

it('lists modules ordered by period then title', function () {
    $ctx = makeSyllabusModuleContext();

    $semesterTwoModuleB = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterTwo']->id,
        'title' => 'Module B',
        'code' => 'S2-B-'.uniqid(),
        'shared' => false,
    ]);

    $semesterOneModuleB = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Module B',
        'code' => 'S1-B-'.uniqid(),
        'shared' => false,
    ]);

    $semesterTwoModuleA = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterTwo']->id,
        'title' => 'Module A',
        'code' => 'S2-A-'.uniqid(),
        'shared' => false,
    ]);

    $semesterOneModuleA = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Module A',
        'code' => 'S1-A-'.uniqid(),
        'shared' => false,
    ]);

    $response = $this->actingAs($ctx['user'])->get(route('course-syllabus-modules.index', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('data.0.id', $semesterOneModuleA->id)
        ->assertJsonPath('data.1.id', $semesterOneModuleB->id)
        ->assertJsonPath('data.2.id', $semesterTwoModuleA->id)
        ->assertJsonPath('data.3.id', $semesterTwoModuleB->id);
});

it('lists only modules for the selected course syllabus', function () {
    $ctx = makeSyllabusModuleContext();

    $otherSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'institution_department_id' => $ctx['institutionDepartment']->id,
        'department_level_course_id' => $ctx['courseSyllabus']->department_level_course_id,
        'title' => 'Other Syllabus '.uniqid(),
        'code' => 'OS-'.uniqid(),
        'implementation_year' => '2026',
        'status' => 'terminated',
    ]);

    $visible = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Visible Module',
        'code' => 'VM-'.uniqid(),
        'shared' => false,
    ]);

    CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $otherSyllabus->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Hidden Module',
        'code' => 'HM-'.uniqid(),
        'shared' => false,
    ]);

    $response = $this->actingAs($ctx['user'])->get(route('course-syllabus-modules.index', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('data.0.id', $visible->id)
        ->assertJsonCount(1, 'data');
});

it('stores a syllabus course module', function () {
    $ctx = makeSyllabusModuleContext();

    $response = $this->actingAs($ctx['user'])->post(route('course-syllabus-modules.store'), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Intro Module',
        'code' => 'IM-'.uniqid(),
        'duration_in_hours' => 16,
        'nql_level' => 5,
        'prerequisite_module_ids' => [],
        'shared' => true,
    ]);

    $response->assertSuccessful();

    $module = CourseSyllabusModule::query()->where('title', 'Intro Module')->first();
    expect($module)->not->toBeNull()
        ->and((int) $module?->academic_year_option_id)->toBe($ctx['semesterOne']->id)
        ->and((bool) $module?->shared)->toBeTrue();
});

it('rejects academic year option that does not match level calendar type', function () {
    $ctx = makeSyllabusModuleContext();

    $response = $this->actingAs($ctx['user'])->post(route('course-syllabus-modules.store'), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['termOne']->id,
        'title' => 'Wrong Period Module',
        'code' => 'WP-'.uniqid(),
        'prerequisite_module_ids' => [],
        'shared' => false,
    ]);

    $response->assertSessionHasErrors('academic_year_option_id');
});

it('updates a syllabus course module', function () {
    $ctx = makeSyllabusModuleContext();

    $module = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Old Module',
        'code' => 'OLD-'.uniqid(),
        'shared' => false,
    ]);

    $response = $this->actingAs($ctx['user'])->put(route('course-syllabus-modules.update', $module), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterTwo']->id,
        'title' => 'Updated Module',
        'code' => $module->code,
        'duration_in_hours' => 24,
        'nql_level' => 6,
        'prerequisite_module_ids' => [],
        'shared' => true,
    ]);

    $response->assertOk();
    $module->refresh();

    expect($module->title)->toBe('Updated Module')
        ->and($module->duration_in_hours)->toBe(24)
        ->and((int) $module->academic_year_option_id)->toBe($ctx['semesterTwo']->id)
        ->and((bool) $module->shared)->toBeTrue();
});

it('paginates modules using page and page_size query parameters', function () {
    $ctx = makeSyllabusModuleContext();

    for ($index = 1; $index <= 12; $index++) {
        CourseSyllabusModule::query()->create([
            'tenant_id' => $ctx['tenant']->id,
            'course_syllabus_id' => $ctx['courseSyllabus']->id,
            'academic_year_option_id' => $ctx['semesterOne']->id,
            'title' => "Module {$index}",
            'code' => "MOD-{$index}-".uniqid(),
            'shared' => false,
        ]);
    }

    $response = $this->actingAs($ctx['user'])->get(route('course-syllabus-modules.index', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
        'page' => 2,
        'page_size' => 5,
    ]));

    $response->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.last_page', 3);
});

it('moves modules to another academic year option', function () {
    $ctx = makeSyllabusModuleContext();

    $moduleA = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Module A',
        'code' => 'MA-'.uniqid(),
        'shared' => false,
    ]);

    $moduleB = CourseSyllabusModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Module B',
        'code' => 'MB-'.uniqid(),
        'shared' => false,
    ]);

    $response = $this->actingAs($ctx['user'])->post(route('course-syllabus-modules.move', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
    ]), [
        'course_syllabus_module_ids' => [$moduleA->id, $moduleB->id],
        'target_academic_year_option_id' => $ctx['semesterTwo']->id,
    ]);

    $response->assertRedirect();
    expect((int) $moduleA->refresh()->academic_year_option_id)->toBe($ctx['semesterTwo']->id)
        ->and((int) $moduleB->refresh()->academic_year_option_id)->toBe($ctx['semesterTwo']->id);
});

it('forbids listing modules without module permissions', function () {
    $ctx = makeSyllabusModuleContext();
    $ctx['user']->revokePermissionTo([
        'viewAny:course-syllabus-modules',
        'view:course-syllabus-modules',
    ]);

    $response = $this->actingAs($ctx['user'])->get(route('course-syllabus-modules.index', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
    ]));

    $response->assertForbidden();
});

it('forbids creating modules without module create permission', function () {
    $ctx = makeSyllabusModuleContext();
    $ctx['user']->revokePermissionTo('create:course-syllabus-modules');

    $response = $this->actingAs($ctx['user'])->post(route('course-syllabus-modules.store'), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'academic_year_option_id' => $ctx['semesterOne']->id,
        'title' => 'Unauthorized Module',
        'code' => 'UN-'.uniqid(),
        'shared' => false,
    ]);

    $response->assertForbidden();
});
