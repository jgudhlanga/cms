<?php

use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelRequirement;
use App\Models\Institution\Grade;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Subject;
use App\Models\Tenants\Tenant;
use App\Rules\Students\ValidateOLevelResults;
use Database\Seeders\Institution\GradesTableSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    (new GradesTableSeeder)->run();
});

function createOLevelRequirementFixture(int $mainCount = 1, int $otherCount = 0): array
{
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
    ]);
    $level = Level::factory()->create();
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $subjects = collect(range(1, max($mainCount, 1)))->map(
        fn (int $index) => Subject::factory()->create(['name' => "Subject {$index}"])
    );

    $requirement = new DepartmentLevelRequirement([
        'department_level_id' => $departmentLevel->id,
        'is_o_level_required' => true,
        'required_subjects_count' => $mainCount + $otherCount,
        'main_subjects_count' => $mainCount,
        'main_subject_ids' => $subjects->pluck('id')->all(),
        'other_subjects_count' => $otherCount,
        'only_read_write_required' => false,
        'required_level_id' => null,
    ]);
    $requirement->tenant_id = $tenant->id;
    $requirement->save();

    $gradeA = Grade::query()->where('name', 'A')->firstOrFail();

    return [$departmentLevel, $departmentCourse, $subjects, $gradeA];
}

function validateOLevelPayload(array $payload): Illuminate\Validation\Validator
{
    $request = Request::create('/', 'POST', $payload);
    $validator = Validator::make($payload, []);

    app(ValidateOLevelResults::class)->validate($request, $validator);

    return $validator;
}

test('o level validation rejects missing exam year', function () {
    [$departmentLevel, $departmentCourse, $subjects, $gradeA] = createOLevelRequirementFixture();
    $subjectId = (string) $subjects->first()->id;

    $validator = validateOLevelPayload([
        'department_id' => $departmentLevel->institution_department_id,
        'level_id' => $departmentLevel->id,
        'course_id' => $departmentCourse->id,
        'date_of_birth' => now()->subYears(18)->toDateString(),
        'o_level_subject_ids' => [$subjectId => (string) $gradeA->id],
        'o_level_years' => [],
        'o_level_sittings' => [$subjectId => ['value' => 'june', 'label' => 'June']],
        'o_level_other_subject_ids' => [],
        'o_level_other_grade_ids' => [],
        'o_level_other_years' => [],
        'o_level_other_sittings' => [],
    ]);

    expect($validator->errors()->has('o_level'))->toBeTrue()
        ->and($validator->errors()->first('o_level'))->toContain('Exam year is required');
});

test('o level validation rejects exam year outside allowed range', function () {
    [$departmentLevel, $departmentCourse, $subjects, $gradeA] = createOLevelRequirementFixture();
    $subjectId = (string) $subjects->first()->id;
    $invalidYear = (int) now()->format('Y') - 50;

    $validator = validateOLevelPayload([
        'department_id' => $departmentLevel->institution_department_id,
        'level_id' => $departmentLevel->id,
        'course_id' => $departmentCourse->id,
        'date_of_birth' => now()->subYears(18)->toDateString(),
        'o_level_subject_ids' => [$subjectId => (string) $gradeA->id],
        'o_level_years' => [$subjectId => (string) $invalidYear],
        'o_level_sittings' => [$subjectId => ['value' => 'june', 'label' => 'June']],
        'o_level_other_subject_ids' => [],
        'o_level_other_grade_ids' => [],
        'o_level_other_years' => [],
        'o_level_other_sittings' => [],
    ]);

    expect($validator->errors()->has('o_level'))->toBeTrue()
        ->and($validator->errors()->first('o_level'))->toContain('Exam year must be between');
});

test('o level validation accepts valid main subject results', function () {
    [$departmentLevel, $departmentCourse, $subjects, $gradeA] = createOLevelRequirementFixture();
    $subjectId = (string) $subjects->first()->id;
    $examYear = (string) ((int) now()->format('Y') - 1);

    $validator = validateOLevelPayload([
        'department_id' => $departmentLevel->institution_department_id,
        'level_id' => $departmentLevel->id,
        'course_id' => $departmentCourse->id,
        'date_of_birth' => now()->subYears(18)->toDateString(),
        'o_level_subject_ids' => [$subjectId => (string) $gradeA->id],
        'o_level_years' => [$subjectId => $examYear],
        'o_level_sittings' => [$subjectId => ['value' => 'june', 'label' => 'June']],
        'o_level_other_subject_ids' => [],
        'o_level_other_grade_ids' => [],
        'o_level_other_years' => [],
        'o_level_other_sittings' => [],
    ]);

    expect($validator->errors()->has('o_level'))->toBeFalse();
});
