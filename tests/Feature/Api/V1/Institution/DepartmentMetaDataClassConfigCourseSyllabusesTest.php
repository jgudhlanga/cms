<?php

use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('class config course syllabuses returns syllabi scoped to department course and level', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'meta-syl-api',
        'description' => 'Metadata syllabus test',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'Meta Syl Level']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $dlc = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $syllabus = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $dlc->id,
        'title' => 'Meta syllabus title '.$departmentCourse->id,
        'code' => 'META-SYL-'.$departmentCourse->id,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    Sanctum::actingAs($user);

    $url = route('v1.department-metadata.class-config-course-syllabuses', [
        'institution_department' => $institutionDepartment->id,
    ]);

    $response = $this->getJson($url.'?department_course_id='.$departmentCourse->id.'&department_level_id='.$departmentLevel->id);

    $response->assertOk();
    $response->assertJsonFragment([
        'id' => $syllabus->id,
        'type' => 'course-syllabus',
    ]);
    $response->assertJsonPath('0.attributes.code', $syllabus->code);
});

test('class config course syllabuses returns 404 when department course belongs to another department', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'meta-syl-other',
        'description' => 'Other dept',
    ]);

    $otherDepartment = Department::factory()->create();
    $otherInstitutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $otherDepartment->id,
        'department_code' => 'meta-syl-owner',
        'description' => 'Owner dept',
    ]);

    $course = Course::factory()->create();
    $foreignCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $otherInstitutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    Sanctum::actingAs($user);

    $url = route('v1.department-metadata.class-config-course-syllabuses', [
        'institution_department' => $institutionDepartment->id,
    ]);

    $this->getJson($url.'?department_course_id='.$foreignCourse->id.'&department_level_id='.$departmentLevel->id)
        ->assertNotFound();
});

test('class config course syllabuses requires query parameters', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'meta-syl-422',
        'description' => '422 test',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('v1.department-metadata.class-config-course-syllabuses', [
        'institution_department' => $institutionDepartment->id,
    ]))->assertUnprocessable();
});
