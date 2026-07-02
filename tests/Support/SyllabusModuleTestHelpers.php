<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Acl\RoleEnum;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Acl\Role;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Staff;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Database\Seeders\Acl\RoleGroupSeeder;
use Database\Seeders\Acl\RolesTableSeeder;

function ensureSyllabusModuleRoles(): void
{
    (new RoleGroupSeeder)->run();
    (new RolesTableSeeder)->run();

    foreach (RoleEnum::cases() as $roleEnum) {
        Role::query()
            ->where('name', $roleEnum->name())
            ->update(['slug' => $roleEnum->value]);
    }
}

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

function makeSyllabusModuleLecturerStaff(array $context): Staff
{
    ensureSyllabusModuleRoles();

    $lecturerUser = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Module',
        'last_name' => 'Lecturer',
    ]);
    $lecturerUser->assignRole(Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail());

    $title = Title::query()->firstOrCreate(['name' => 'Mr Module Lecturer']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Module Lecturer']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Module Lecturer']);
    $employmentType = EmploymentType::query()->firstOrCreate(
        ['name' => 'full-time-module-lecturer'],
        ['description' => 'Full time'],
    );

    $staff = Staff::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $lecturerUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => 'MOD-LEC-'.uniqid(),
    ]);

    $staff->institutionDepartments()->attach($context['institutionDepartment']->id);

    return $staff;
}

function makeSyllabusModuleNonLecturerStaff(array $context): Staff
{
    ensureSyllabusModuleRoles();

    $user = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    $user->assignRole(Role::query()->where('name', RoleEnum::HR_OFFICER->name())->firstOrFail());

    $title = Title::query()->firstOrCreate(['name' => 'Ms Module Non Lecturer']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Female Module Non Lecturer']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single Module Non Lecturer']);
    $employmentType = EmploymentType::query()->firstOrCreate(
        ['name' => 'full-time-module-non-lecturer'],
        ['description' => 'Full time'],
    );

    $staff = Staff::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $user->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => 'MOD-NL-'.uniqid(),
    ]);

    $staff->institutionDepartments()->attach($context['institutionDepartment']->id);

    return $staff;
}
