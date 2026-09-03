<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\Course;
use App\Models\Institution\CourseLevelMode;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

test('department levels metadata includes calendar types from subscribed levels', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'ict-cal-types',
        'description' => 'ICT calendar type metadata',
    ]);

    $semesterLevel = Level::factory()->create([
        'name' => 'National Certificate',
        'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
    ]);
    $abmaLevel = Level::factory()->create([
        'name' => 'ABMA Diploma',
        'calendar_type' => AcademicCalendarTypeEnum::ABMA,
    ]);
    $termLevel = Level::factory()->create([
        'name' => 'SDP',
        'calendar_type' => AcademicCalendarTypeEnum::TERM,
    ]);

    DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $semesterLevel->id,
    ]);
    DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $abmaLevel->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.department-metadata.levels', $institutionDepartment->id));

    $response->assertOk();
    $types = collect($response->json('levels.data') ?? $response->json('levels') ?? [])
        ->map(fn ($row) => data_get($row, 'attributes.calendarType'))
        ->filter()
        ->unique()
        ->values()
        ->all();

    expect($types)->toContain('semester')
        ->and($types)->toContain('abma')
        ->and($types)->not->toContain('term');

    expect(Level::query()->whereKey($termLevel->id)->exists())->toBeTrue();
});

test('department modes metadata returns only subscribed course level modes', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'ict-modes',
        'description' => 'ICT subscribed modes',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['calendar_type' => AcademicCalendarTypeEnum::SEMESTER]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $fullTime = ModeOfStudy::query()->create(['name' => 'Full Time ICT']);
    $partTime = ModeOfStudy::query()->create(['name' => 'Part Time ICT']);
    $blockRelease = ModeOfStudy::query()->create(['name' => 'Block Release ICT']);

    CourseLevelMode::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'modes' => [$fullTime->id, $partTime->id],
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('v1.department-metadata.modes', $institutionDepartment->id));

    $response->assertOk();
    $payload = $response->json();
    $rows = is_array($payload) && array_is_list($payload) ? $payload : ($payload['data'] ?? []);
    $ids = collect($rows)->pluck('id')->map(fn ($id) => (int) $id)->all();

    expect($ids)->toContain($fullTime->id)
        ->and($ids)->toContain($partTime->id)
        ->and($ids)->not->toContain($blockRelease->id);
});
