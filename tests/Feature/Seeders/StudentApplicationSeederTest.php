<?php

declare(strict_types=1);

use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\GradeEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Institution\SubjectEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\DisabilityStatusEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\RelationshipEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\Grade;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Subject;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Race;
use App\Models\Shared\Relationship;
use App\Models\Shared\Title;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use Database\Seeders\Shared\AcademicLevelsTableSeeder;
use Database\Seeders\Students\StudentApplicationSeeder;

function seedStudentApplicationSeederLookups(): void
{
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');

    Gender::query()->firstOrCreate(['title' => 'Male']);
    Title::query()->firstOrCreate(['name' => 'Mr']);
    MaritalStatus::query()->firstOrCreate(['title' => 'Single']);
    Race::query()->firstOrCreate(['title' => 'African']);
    Relationship::query()->firstOrCreate(['name' => RelationshipEnum::PARENT->value]);
    (new AcademicLevelsTableSeeder)->run();
    WorkflowStep::query()->firstOrCreate(
        ['position' => 1],
        ['name' => 'Step One', 'description' => 'Initial application step'],
    );

    if (! IdType::query()->where('name', IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value)->exists()) {
        IdType::query()->create([
            'name' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value,
            'description' => 'National ID',
        ]);
    }

    foreach ([GradeEnum::A, GradeEnum::B, GradeEnum::C] as $index => $grade) {
        Grade::query()->firstOrCreate(
            ['name' => $grade->value],
            ['position' => $index + 1],
        );
    }

    $subjects = [
        SubjectEnum::ENGLISH,
        SubjectEnum::MATHEMATICS,
        SubjectEnum::INTEGRATED_SCIENCE,
        SubjectEnum::AGRICULTURE,
        SubjectEnum::BIBLE_KNOWLEDGE,
    ];

    foreach ($subjects as $subject) {
        Subject::query()->firstOrCreate(
            ['name' => $subject->value],
            ['position' => $subject->id()],
        );
    }
}

/**
 * @return array{institutionDepartment: InstitutionDepartment, departmentLevel: DepartmentLevel, departmentCourse: DepartmentCourse, modeId: int}
 */
function seedScienceOrthopedicOffering(): array
{
    $tenantId = TenantEnum::HARARE_POLY->id();
    $departmentEnum = DepartmentEnum::SCIENCE_TECHNOLOGY;

    $department = Department::query()->firstOrCreate(
        ['name' => $departmentEnum->value],
        ['description' => $departmentEnum->value, 'is_academic' => true],
    );
    $department->update(['is_academic' => true]);

    $institutionDepartment = InstitutionDepartment::query()->firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'department_id' => $department->id,
        ],
        ['department_code' => 'SCI'],
    );

    $level = Level::query()->firstOrCreate(
        ['name' => LevelEnum::NC->value],
        ['description' => 'NC', 'position' => 5, 'show_on_current_application_period' => true],
    );

    $departmentLevel = DepartmentLevel::query()->firstOrCreate([
        'tenant_id' => $tenantId,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $course = Course::query()->firstOrCreate(
        ['name' => 'Orthopedic Technology'],
        ['description' => 'Orthopedic Technology'],
    );

    $departmentCourse = DepartmentCourse::query()->firstOrCreate([
        'tenant_id' => $tenantId,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $mode = ModeOfStudy::query()->firstOrCreate(
        ['name' => ModeOfStudyEnum::FULL_TIME->value],
        ['description' => 'Full Time'],
    );

    seedApplicationOffering(
        $institutionDepartment,
        $departmentLevel,
        $departmentCourse,
        [(int) $mode->id],
    );

    return [
        'institutionDepartment' => $institutionDepartment,
        'departmentLevel' => $departmentLevel,
        'departmentCourse' => $departmentCourse,
        'modeId' => (int) $mode->id,
    ];
}

beforeEach(function (): void {
    seedStudentApplicationSeederLookups();
    ensureCurrentIntakeStatus(IntakePeriodStatusEnum::Open->value);
    seedScienceOrthopedicOffering();
});

test('seeds applications for Science Technology NC Orthopedic Technology only', function (): void {
    $intake = IntakePeriod::query()
        ->where('tenant_id', TenantEnum::HARARE_POLY->id())
        ->where('is_continuous', false)
        ->where('is_active', true)
        ->orderByDesc('end_date')
        ->firstOrFail();

    $institutionDepartment = InstitutionDepartment::query()
        ->where('tenant_id', TenantEnum::HARARE_POLY->id())
        ->whereHas('department', fn ($query) => $query->where('name', DepartmentEnum::SCIENCE_TECHNOLOGY->value))
        ->firstOrFail();

    $departmentLevel = DepartmentLevel::query()
        ->where('institution_department_id', $institutionDepartment->id)
        ->whereHas('level', fn ($query) => $query->where('name', LevelEnum::NC->value))
        ->firstOrFail();

    $departmentCourse = DepartmentCourse::query()
        ->where('institution_department_id', $institutionDepartment->id)
        ->whereHas('course', fn ($query) => $query->where('name', 'Orthopedic Technology'))
        ->firstOrFail();

    $modeId = (int) ModeOfStudy::query()->where('name', ModeOfStudyEnum::FULL_TIME->value)->value('id');

    $seeder = new StudentApplicationSeeder;
    $seeder->applicationsPerDepartment = 5;
    $seeder->disabledApplications = 2;
    $seeder->run();

    $applications = StudentApplication::query()
        ->where('tenant_id', TenantEnum::HARARE_POLY->id())
        ->with(['student.contacts', 'student.addresses', 'student.nextOfKins', 'student.oLevelResults'])
        ->get();

    expect($applications)->toHaveCount(5);
    expect($applications->filter(fn (StudentApplication $application): bool => $application->student?->disability_status === DisabilityStatusEnum::YES->value))
        ->toHaveCount(2);

    foreach ($applications as $application) {
        expect((int) $application->institution_department_id)->toBe((int) $institutionDepartment->id);
        expect((int) $application->department_level_id)->toBe((int) $departmentLevel->id);
        expect((int) $application->department_course_id)->toBe((int) $departmentCourse->id);
        expect((int) $application->mode_of_study_id)->toBe($modeId);
        expect((int) $application->intake_period_id)->toBe((int) $intake->id);
        expect($application->student?->student_number)->not->toBeEmpty();
        expect($application->student?->contacts)->not->toBeEmpty();
        expect($application->student?->addresses)->not->toBeEmpty();
        expect($application->student?->nextOfKins)->not->toBeEmpty();
        expect($application->student?->oLevelResults)->toHaveCount(5);
        expect($application->application_tracking_number)->not->toBeEmpty();
    }
});

test('always creates a new batch on the current regular intake', function (): void {
    $intake = IntakePeriod::query()
        ->where('tenant_id', TenantEnum::HARARE_POLY->id())
        ->where('is_continuous', false)
        ->where('is_active', true)
        ->orderByDesc('end_date')
        ->firstOrFail();

    $seeder = new StudentApplicationSeeder;
    $seeder->applicationsPerDepartment = 2;
    $seeder->disabledApplications = 1;
    $seeder->run();
    $seeder->run();

    $applications = StudentApplication::query()
        ->where('tenant_id', TenantEnum::HARARE_POLY->id())
        ->get();

    expect($applications)->toHaveCount(4);
    expect($applications->every(fn (StudentApplication $application): bool => (int) $application->intake_period_id === (int) $intake->id))
        ->toBeTrue();
});
