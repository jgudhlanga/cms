<?php

use App\Http\Requests\Institution\SyllabusCourseModuleRequest;
use App\Models\Institution\Course;
use App\Models\Institution\CourseSyllabus;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\SyllabusCourseModule;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\Validator;

function makeSyllabusModuleContext(): array
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

    $level = Level::factory()->create(['name' => 'Level 1']);
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

    return compact('tenant', 'user', 'institutionDepartment', 'courseSyllabus');
}

it('validates required syllabus course module fields', function () {
    $request = new SyllabusCourseModuleRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('course_syllabus_id'))->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
    expect($validator->errors()->has('code'))->toBeTrue();
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

    $visible = SyllabusCourseModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'title' => 'Visible Module',
        'code' => 'VM-'.uniqid(),
        'shared' => false,
    ]);

    SyllabusCourseModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $otherSyllabus->id,
        'title' => 'Hidden Module',
        'code' => 'HM-'.uniqid(),
        'shared' => false,
    ]);

    $response = $this->actingAs($ctx['user'])->get(route('syllabus-course-modules.index', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('data.0.id', $visible->id)
        ->assertJsonCount(1, 'data');
});

it('stores a syllabus course module', function () {
    $ctx = makeSyllabusModuleContext();

    $response = $this->actingAs($ctx['user'])->post(route('syllabus-course-modules.store'), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'title' => 'Intro Module',
        'code' => 'IM-'.uniqid(),
        'duration_in_hours' => 16,
        'nql_level' => 5,
        'prerequisite_module_ids' => [],
        'shared' => true,
    ]);

    $response->assertSuccessful();

    $module = SyllabusCourseModule::query()->where('title', 'Intro Module')->first();
    expect($module)->not->toBeNull()
        ->and((bool) $module?->shared)->toBeTrue();
});

it('updates a syllabus course module', function () {
    $ctx = makeSyllabusModuleContext();

    $module = SyllabusCourseModule::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'title' => 'Old Module',
        'code' => 'OLD-'.uniqid(),
        'shared' => false,
    ]);

    $response = $this->actingAs($ctx['user'])->put(route('syllabus-course-modules.update', $module), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
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
        ->and((bool) $module->shared)->toBeTrue();
});

it('forbids listing modules without module permissions', function () {
    $ctx = makeSyllabusModuleContext();
    $ctx['user']->revokePermissionTo([
        'viewAny:course-syllabus-modules',
        'view:course-syllabus-modules',
    ]);

    $response = $this->actingAs($ctx['user'])->get(route('syllabus-course-modules.index', [
        'institution_department' => $ctx['institutionDepartment']->id,
        'course_syllabus' => $ctx['courseSyllabus']->id,
    ]));

    $response->assertForbidden();
});

it('forbids creating modules without module create permission', function () {
    $ctx = makeSyllabusModuleContext();
    $ctx['user']->revokePermissionTo('create:course-syllabus-modules');

    $response = $this->actingAs($ctx['user'])->post(route('syllabus-course-modules.store'), [
        'course_syllabus_id' => $ctx['courseSyllabus']->id,
        'title' => 'Unauthorized Module',
        'code' => 'UN-'.uniqid(),
        'shared' => false,
    ]);

    $response->assertForbidden();
});
