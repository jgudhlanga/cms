<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarOption;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

function buildDepartmentClassContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'ict',
        'description' => 'Department for classes tests',
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
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);
    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $option = AcademicCalendarOption::query()->create([
        'name' => 'Semester 1',
        'description' => 'Semester 1 option',
    ]);
    $calendar = AcademicCalendar::query()->create([
        'academic_calendar_option_id' => $option->id,
        'calendar_year' => '2026',
        'opening_date' => now()->startOfMonth()->toDateString(),
        'closing_date' => now()->endOfMonth()->toDateString(),
        'intake_period_ids' => [$intakePeriod->id],
    ]);
    $classConfig = ClassConfig::query()->create([
        'academic_calendar_id' => $calendar->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 2,
    ]);

    return compact(
        'tenant',
        'user',
        'institutionDepartment',
        'departmentCourse',
        'departmentLevel',
        'modeOfStudy',
        'intakePeriod',
        'calendar',
        'classConfig'
    );
}

function createFinalStudentProgram(array $context, string $email, string $genderTitle = 'Male'): StudentProgram
{
    $title = Title::query()->create(['name' => 'Mr '.str($email)->before('@')]);
    $gender = Gender::query()->create(['title' => $genderTitle.' '.str($email)->before('@')]);
    $marital = MaritalStatus::query()->create(['title' => 'Single '.str($email)->before('@')]);
    $idType = IdType::query()->create(['name' => 'National ID '.str($email)->before('@')]);
    $studentUser = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'email' => $email,
        'first_name' => 'Student',
        'last_name' => str($email)->before('@')->toString(),
    ]);
    $student = Student::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'id_type_id' => $idType->id,
        'date_of_birth' => '2001-01-01',
    ]);
    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_id' => $student->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'intake_period_id' => $context['intakePeriod']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'application_tracking_number' => 'APP-'.strtoupper(str()->random(8)),
    ]);
    ClassList::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_program_id' => $studentProgram->id,
        'type' => ClassListTypeEnum::FINAL->value,
        'attributes' => [],
    ]);

    return $studentProgram;
}

test('department classes page returns generation context and preview classes', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'student-one@example.com');
    createFinalStudentProgram($context, 'student-two@example.com');
    createFinalStudentProgram($context, 'student-three@example.com');

    $this->actingAs($context['user']);
    $response = $this->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.generationContext.classConfigId'))->toBe($context['classConfig']->id);
    expect(data_get($page, 'props.generationContext.finalStudentCount'))->toBe(3);
    expect(data_get($page, 'props.generationContext.newFinalStudentCount'))->toBe(3);
    expect(data_get($page, 'props.generationContext.hasExistingClasses'))->toBeFalse();
    expect(data_get($page, 'props.generationContext.newStudentGenderCounts.male'))->toBeInt();
    expect(data_get($page, 'props.generationContext.newStudentGenderCounts.female'))->toBeInt();
    expect(data_get($page, 'props.generationContext.newStudentGenderCounts.unknown'))->toBeInt();
    expect(data_get($page, 'props.previewClasses'))->toHaveCount(2);
    expect(data_get($page, 'props.previewClasses.0.name'))->toBe('Level 1 - Full Time - 1');
    expect(data_get($page, 'props.previewClasses.1.name'))->toBe('Level 1 - Full Time - 2');
    expect(data_get($page, 'props.previewClasses.0.genderCounts.male'))->toBeInt();
    expect(data_get($page, 'props.previewClasses.0.genderCounts.female'))->toBeInt();
    expect(data_get($page, 'props.previewClasses.0.genderCounts.unknown'))->toBeInt();
});

test('department academic calendar api returns assigned and ready class counts', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'api-student-one@example.com');
    createFinalStudentProgram($context, 'api-student-two@example.com');
    createFinalStudentProgram($context, 'api-student-three@example.com');

    $this->actingAs($context['user']);

    $apiRoute = route('v1.departments.academic-calendars', [
        'institution_department' => $context['institutionDepartment']->id,
    ]);
    $apiRoute .= '?academic_calendar='.$context['calendar']->id.'&mode_of_study_id='.$context['modeOfStudy']->id;

    $this->getJson($apiRoute)
        ->assertSuccessful()
        ->assertJsonPath('0.levels.0.totalnClass', 0)
        ->assertJsonPath('0.levels.0.totalFinalList', 3);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $this->getJson($apiRoute)
        ->assertSuccessful()
        ->assertJsonPath('0.levels.0.totalnClass', 3)
        ->assertJsonPath('0.levels.0.totalFinalList', 3);
});

test('saving generated classes is idempotent for the same context', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'student-a@example.com');
    createFinalStudentProgram($context, 'student-b@example.com');
    createFinalStudentProgram($context, 'student-c@example.com');

    $this->actingAs($context['user']);

    $payload = [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ];

    $url = route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]);

    $this->post($url, $payload)->assertSessionHas('success');
    $classIdsAfterFirstSave = DB::table('academic_calandar_classes')
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->pluck('id')
        ->all();
    $studentProgramToClassMapAfterFirstSave = DB::table('academic_calendar_student_programs')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_program_id')
        ->all();

    $this->post($url, $payload)->assertSessionHas('success');
    $classIdsAfterSecondSave = DB::table('academic_calandar_classes')
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->pluck('id')
        ->all();
    $studentProgramToClassMapAfterSecondSave = DB::table('academic_calendar_student_programs')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_program_id')
        ->all();

    expect(DB::table('academic_calandar_classes')->whereNull('deleted_at')->count())->toBe(2);
    expect(DB::table('academic_calendar_student_programs')->whereNull('deleted_at')->count())->toBe(3);
    expect($classIdsAfterSecondSave)->toBe($classIdsAfterFirstSave);
    expect($studentProgramToClassMapAfterSecondSave)->toBe($studentProgramToClassMapAfterFirstSave);
});

test('saving generated classes adds only newly-finalized students', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'student-aa1@example.com');
    createFinalStudentProgram($context, 'student-bb1@example.com');
    createFinalStudentProgram($context, 'student-cc1@example.com');

    $this->actingAs($context['user']);

    $payload = [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ];

    $url = route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]);

    $this->post($url, $payload)->assertSessionHas('success');

    $studentProgramToClassMapAfterFirstSave = DB::table('academic_calendar_student_programs')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_program_id')
        ->all();
    $classCountAfterFirstSave = DB::table('academic_calandar_classes')
        ->whereNull('deleted_at')
        ->count();

    createFinalStudentProgram($context, 'student-dd1@example.com');
    createFinalStudentProgram($context, 'student-ee1@example.com');

    $this->post($url, $payload)->assertSessionHas('success');

    $studentProgramToClassMapAfterSecondSave = DB::table('academic_calendar_student_programs')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_program_id')
        ->all();

    expect(DB::table('academic_calendar_student_programs')->whereNull('deleted_at')->count())->toBe(5);
    expect(DB::table('academic_calandar_classes')->whereNull('deleted_at')->count())->toBeGreaterThanOrEqual($classCountAfterFirstSave);

    foreach ($studentProgramToClassMapAfterFirstSave as $studentProgramId => $academicCalendarClassId) {
        expect($studentProgramToClassMapAfterSecondSave[$studentProgramId] ?? null)->toBe($academicCalendarClassId);
    }

    $newlyAssignedClassIds = collect($studentProgramToClassMapAfterSecondSave)
        ->except(array_keys($studentProgramToClassMapAfterFirstSave))
        ->values();

    expect($newlyAssignedClassIds)->not->toBeEmpty();
});

test('department classes page shows existing classes when all final students are already assigned', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'assigned-one@example.com');
    createFinalStudentProgram($context, 'assigned-two@example.com');
    createFinalStudentProgram($context, 'assigned-three@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $response = $this->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.generationContext.newFinalStudentCount'))->toBe(0);
    expect(data_get($page, 'props.previewClasses'))->toHaveCount(2);
    expect(data_get($page, 'props.previewClasses.0.academicCalendarClassId'))->toBeInt();
    expect(data_get($page, 'props.previewClasses.1.academicCalendarClassId'))->toBeInt();
});

test('saving generated classes balances gender when both genders exist', function () {
    $context = buildDepartmentClassContext();
    $context['classConfig']->update(['students_per_class' => 5]);

    foreach (range(1, 6) as $index) {
        createFinalStudentProgram($context, "male-student-{$index}@example.com", 'Male');
    }

    foreach (range(1, 5) as $index) {
        createFinalStudentProgram($context, "female-student-{$index}@example.com", 'Female');
    }

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 5,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->orderBy('id')
        ->get();

    expect($classes)->toHaveCount(3);

    foreach ($classes as $class) {
        $genderCounts = DB::table('academic_calendar_student_programs')
            ->join('student_programs', 'student_programs.id', '=', 'academic_calendar_student_programs.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_programs.academic_calendar_class_id', $class->id)
            ->whereNull('academic_calendar_student_programs.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count
            ")
            ->first();

        $maleCount = (int) ($genderCounts->male_count ?? 0);
        $femaleCount = (int) ($genderCounts->female_count ?? 0);

        if ($maleCount > 0 && $femaleCount > 0) {
            expect(abs($maleCount - $femaleCount))->toBeLessThanOrEqual(1);
        }
    }

    expect(DB::table('academic_calendar_student_programs')->whereNull('deleted_at')->count())->toBe(11);
});

test('saving generated classes works with one available gender', function () {
    $context = buildDepartmentClassContext();

    foreach (range(1, 5) as $index) {
        createFinalStudentProgram($context, "single-gender-{$index}@example.com", 'Male');
    }

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->orderBy('id')
        ->get();

    expect($classes)->toHaveCount(3);

    foreach ($classes as $class) {
        $genderCounts = DB::table('academic_calendar_student_programs')
            ->join('student_programs', 'student_programs.id', '=', 'academic_calendar_student_programs.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_programs.academic_calendar_class_id', $class->id)
            ->whereNull('academic_calendar_student_programs.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count
            ")
            ->first();

        expect((int) ($genderCounts->female_count ?? 0))->toBe(0);
    }
});

test('class detail page returns class metadata and students', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'student-aa@example.com');
    createFinalStudentProgram($context, 'student-bb@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $academicCalendarClass = AcademicCalendarClass::query()->firstOrFail();

    $response = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'academic_calendar' => $context['calendar']->id,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.academicCalendarClass.name'))->toBe($academicCalendarClass->name)
        ->and(data_get($page, 'props.academicCalendarClass.studentCount'))->toBe(2)
        ->and(data_get($page, 'props.academicCalendarClass.students'))->toHaveCount(2)
        ->and(data_get($page, 'props.academicCalendarClass.metadata'))->not->toBeEmpty()
        ->and(data_get($page, 'props.course'))->not->toBeNull()
        ->and(data_get($page, 'props.level'))->not->toBeNull()
        ->and(data_get($page, 'props.mode'))->not->toBeNull()
        ->and(data_get($page, 'props.classConfig'))->not->toBeNull();
});
