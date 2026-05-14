<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\AcademicYearOption;
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
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
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
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->subDays(30)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);
    $classConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
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

    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Test year option '.$studentProgram->id,
        'description' => null,
    ]);
    $enrolmentStatus = StudentEnrolmentStatus::query()->create([
        'name' => 'Active enrolment '.$studentProgram->id,
        'description' => 'Test',
    ]);

    StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $context['calendar']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'student_enrolment_status_id' => $enrolmentStatus->id,
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
        'calendar_year' => $context['calendar']->calendar_year,
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
    expect(data_get($page, 'props.generationContext.populatedExistingClassCount'))->toBe(0);
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

test('department classes page includes final enrolments from any started academic calendar in the year', function () {
    $context = buildDepartmentClassContext();

    AcademicCalendar::query()->create([
        'calendar_year' => $context['calendar']->calendar_year,
        'opening_date' => now()->subDays(5)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    createFinalStudentProgram($context, 'student-on-older-calendar@example.com');

    $this->actingAs($context['user']);
    $response = $this->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.generationContext.finalStudentCount'))->toBe(1)
        ->and(data_get($page, 'props.generationContext.newFinalStudentCount'))->toBe(1);
});

test('preview merges trailing class when remainder is below half of students per class', function () {
    $context = buildDepartmentClassContext();
    $context['classConfig']->update(['students_per_class' => 10]);

    for ($i = 1; $i <= 24; $i++) {
        createFinalStudentProgram($context, "merge-below-half-{$i}@example.com");
    }

    $this->actingAs($context['user']);
    $response = $this->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.previewClasses'))->toHaveCount(2);
    expect(data_get($page, 'props.previewClasses.0.studentCount'))->toBe(10);
    expect(data_get($page, 'props.previewClasses.1.studentCount'))->toBe(14);
});

test('preview keeps separate trailing class when remainder is at least half of students per class', function () {
    $context = buildDepartmentClassContext();
    $context['classConfig']->update(['students_per_class' => 10]);

    for ($i = 1; $i <= 25; $i++) {
        createFinalStudentProgram($context, "merge-at-least-half-{$i}@example.com");
    }

    $this->actingAs($context['user']);
    $response = $this->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.previewClasses'))->toHaveCount(3);
    expect(data_get($page, 'props.previewClasses.0.studentCount'))->toBe(10);
    expect(data_get($page, 'props.previewClasses.1.studentCount'))->toBe(10);
    expect(data_get($page, 'props.previewClasses.2.studentCount'))->toBe(5);
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
    $apiRoute .= '?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id;

    $this->getJson($apiRoute)
        ->assertSuccessful()
        ->assertJsonPath('data.0.levels.0.classesCount', 0)
        ->assertJsonPath('data.0.levels.0.totalnClass', 3)
        ->assertJsonPath('data.0.levels.0.totalFinalList', 3);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $expectedClassesCount = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->whereNull('deleted_at')
        ->whereHas('studentEnrolments', function ($query): void {
            $query->whereNull('deleted_at');
        })
        ->count();

    $this->getJson($apiRoute)
        ->assertSuccessful()
        ->assertJsonPath('data.0.levels.0.classesCount', $expectedClassesCount)
        ->assertJsonPath('data.0.levels.0.totalnClass', 3)
        ->assertJsonPath('data.0.levels.0.totalFinalList', 3);
});

test('department academic calendar api total final list is scoped by intake calendar year', function () {
    $context = buildDepartmentClassContext();

    $intakeB = IntakePeriod::query()->create([
        'tenant_id' => $context['tenant']->id,
        'name' => 'Semester 2 2027',
        'calendar_year' => '2027',
        'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonth()->endOfMonth()->toDateString(),
    ]);

    $calendar2027 = AcademicCalendar::query()->create([
        'calendar_year' => '2027',
        'opening_date' => now()->subDays(5)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    ClassConfig::query()->create([
        'calendar_year' => $calendar2027->calendar_year,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ]);

    createFinalStudentProgram($context, 'intake-a-one@example.com');
    createFinalStudentProgram($context, 'intake-a-two@example.com');

    $contextForIntakeB = array_merge($context, ['intakePeriod' => $intakeB]);
    createFinalStudentProgram($contextForIntakeB, 'intake-b-one@example.com');

    $this->actingAs($context['user']);

    $route2026 = route('v1.departments.academic-calendars', [
        'institution_department' => $context['institutionDepartment']->id,
    ]).'?academic_year='.$context['calendar']->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id;

    $route2027 = route('v1.departments.academic-calendars', [
        'institution_department' => $context['institutionDepartment']->id,
    ]).'?academic_year='.$calendar2027->calendar_year.'&mode_of_study_id='.$context['modeOfStudy']->id;

    $this->getJson($route2026)
        ->assertSuccessful()
        ->assertJsonPath('data.0.levels.0.totalFinalList', 2);

    $this->getJson($route2027)
        ->assertSuccessful()
        ->assertJsonPath('data.0.levels.0.totalFinalList', 1);
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
        'calendar_year' => $context['calendar']->calendar_year,
    ]);

    $this->post($url, $payload)->assertSessionHas('success');
    $classIdsAfterFirstSave = DB::table('academic_calandar_classes')
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->pluck('id')
        ->all();
    $studentProgramToClassMapAfterFirstSave = DB::table('academic_calendar_student_enrolments')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_enrolment_id')
        ->all();

    $this->post($url, $payload)->assertSessionHas('success');
    $classIdsAfterSecondSave = DB::table('academic_calandar_classes')
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->pluck('id')
        ->all();
    $studentProgramToClassMapAfterSecondSave = DB::table('academic_calendar_student_enrolments')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_enrolment_id')
        ->all();

    expect(DB::table('academic_calandar_classes')->whereNull('deleted_at')->count())->toBe(2);
    expect(DB::table('academic_calendar_student_enrolments')->whereNull('deleted_at')->count())->toBe(3);
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
        'calendar_year' => $context['calendar']->calendar_year,
    ]);

    $this->post($url, $payload)->assertSessionHas('success');

    $studentProgramToClassMapAfterFirstSave = DB::table('academic_calendar_student_enrolments')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_enrolment_id')
        ->all();
    $classCountAfterFirstSave = DB::table('academic_calandar_classes')
        ->whereNull('deleted_at')
        ->count();

    createFinalStudentProgram($context, 'student-dd1@example.com');
    createFinalStudentProgram($context, 'student-ee1@example.com');

    $this->post($url, $payload)->assertSessionHas('success');

    $studentProgramToClassMapAfterSecondSave = DB::table('academic_calendar_student_enrolments')
        ->whereNull('deleted_at')
        ->pluck('academic_calendar_class_id', 'student_enrolment_id')
        ->all();

    expect(DB::table('academic_calendar_student_enrolments')->whereNull('deleted_at')->count())->toBe(5);
    expect(DB::table('academic_calandar_classes')->whereNull('deleted_at')->count())->toBeGreaterThanOrEqual($classCountAfterFirstSave);

    foreach ($studentProgramToClassMapAfterFirstSave as $studentEnrolmentId => $academicCalendarClassId) {
        expect($studentProgramToClassMapAfterSecondSave[$studentEnrolmentId] ?? null)->toBe($academicCalendarClassId);
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
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $response = $this->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.generationContext.newFinalStudentCount'))->toBe(0);
    expect(data_get($page, 'props.generationContext.populatedExistingClassCount'))->toBe(2);
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
        'calendar_year' => $context['calendar']->calendar_year,
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

    expect($classes)->toHaveCount(2);

    foreach ($classes as $class) {
        $enrollmentCount = AcademicCalendarStudentEnrolment::query()
            ->where('academic_calendar_class_id', $class->id)
            ->whereNull('deleted_at')
            ->count();

        $genderCounts = DB::table('academic_calendar_student_enrolments')
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_programs', 'student_programs.id', '=', 'student_enrolments.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $class->id)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count
            ")
            ->first();

        $maleCount = (int) ($genderCounts->male_count ?? 0);
        $femaleCount = (int) ($genderCounts->female_count ?? 0);

        if ($maleCount > 0 && $femaleCount > 0 && $enrollmentCount <= 5) {
            expect(abs($maleCount - $femaleCount))->toBeLessThanOrEqual(1);
        }
    }

    expect(DB::table('academic_calendar_student_enrolments')->whereNull('deleted_at')->count())->toBe(11);
});

test('saving generated classes works with one available gender', function () {
    $context = buildDepartmentClassContext();

    foreach (range(1, 5) as $index) {
        createFinalStudentProgram($context, "single-gender-{$index}@example.com", 'Male');
    }

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
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
        $genderCounts = DB::table('academic_calendar_student_enrolments')
            ->join('student_enrolments', 'student_enrolments.id', '=', 'academic_calendar_student_enrolments.student_enrolment_id')
            ->join('student_programs', 'student_programs.id', '=', 'student_enrolments.student_program_id')
            ->join('students', 'students.id', '=', 'student_programs.student_id')
            ->join('genders', 'genders.id', '=', 'students.gender_id')
            ->where('academic_calendar_student_enrolments.academic_calendar_class_id', $class->id)
            ->whereNull('academic_calendar_student_enrolments.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN LOWER(genders.title) LIKE 'male%' THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN LOWER(genders.title) LIKE 'female%' THEN 1 ELSE 0 END) as female_count
            ")
            ->first();

        expect((int) ($genderCounts->female_count ?? 0))->toBe(0);
    }
});

test('class detail page returns students', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'student-aa@example.com');
    createFinalStudentProgram($context, 'student-bb@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
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
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    expect(data_get($page, 'props.academicCalendarClass.name'))->toBe($academicCalendarClass->name)
        ->and(data_get($page, 'props.academicCalendarClass.studentCount'))->toBe(2)
        ->and(data_get($page, 'props.academicCalendarClass.students'))->toHaveCount(2)
        ->and(data_get($page, 'props.course'))->not->toBeNull()
        ->and(data_get($page, 'props.level'))->not->toBeNull()
        ->and(data_get($page, 'props.mode'))->not->toBeNull()
        ->and(data_get($page, 'props.classConfig'))->not->toBeNull()
        ->and(data_get($page, 'props.canUpdateAcademicCalendarStudentEnrolments'))->toBeFalse()
        ->and(data_get($page, 'props.moveTargetClasses'))->toBe([])
        ->and(data_get($page, 'props.siblingAcademicCalendarClasses'))->toHaveCount(1)
        ->and(data_get($page, 'props.siblingAcademicCalendarClasses.0.id'))->toBe($academicCalendarClass->id);
});

test('class detail page exposes move targets and update flag when user has permission and multiple classes exist', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'move-a@example.com');
    createFinalStudentProgram($context, 'move-b@example.com');
    createFinalStudentProgram($context, 'move-c@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->get();

    expect($classes)->toHaveCount(2);

    $classA = $classes->first();

    $response = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    $siblingIds = collect(data_get($page, 'props.siblingAcademicCalendarClasses', []))->pluck('id')->all();

    expect(data_get($page, 'props.moveTargetClasses'))->toHaveCount(1)
        ->and(data_get($page, 'props.siblingAcademicCalendarClasses'))->toHaveCount(2)
        ->and($siblingIds)->toContain($classA->id)
        ->and(data_get($page, 'props.canUpdateAcademicCalendarStudentEnrolments'))->toBeFalse();

    $context['user']->givePermissionTo('update:academic-calendar-student-enrolments');

    $responseAuthorized = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]));

    expect(data_get($responseAuthorized->viewData('page'), 'props.canUpdateAcademicCalendarStudentEnrolments'))->toBeTrue();
});

test('authorized user can move students to another class in the same config', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'auth-move-a@example.com');
    createFinalStudentProgram($context, 'auth-move-b@example.com');
    createFinalStudentProgram($context, 'auth-move-c@example.com');

    $context['user']->givePermissionTo('update:academic-calendar-student-enrolments');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->get();

    $classA = $classes->first();
    $classB = $classes->last();

    $studentEnrolmentId = (int) DB::table('academic_calendar_student_enrolments')
        ->where('academic_calendar_class_id', $classA->id)
        ->whereNull('deleted_at')
        ->value('student_enrolment_id');

    expect($studentEnrolmentId)->toBeGreaterThan(0);

    $moveUrl = route('academic-calendars.department-classes.move-students', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]);

    $this->post($moveUrl, [
        'student_enrolment_ids' => [$studentEnrolmentId],
        'target_academic_calendar_class_id' => $classB->id,
    ])->assertSessionHas('success');

    expect(
        (int) DB::table('academic_calendar_student_enrolments')
            ->where('student_enrolment_id', $studentEnrolmentId)
            ->whereNull('deleted_at')
            ->value('academic_calendar_class_id')
    )->toBe($classB->id);

    $showClassA = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]));

    $showClassA->assertSuccessful();
    $pageClassA = $showClassA->viewData('page');

    expect(data_get($pageClassA, 'props.academicCalendarClass.studentCount'))->toBe(1)
        ->and(data_get($pageClassA, 'props.academicCalendarClass.students'))->toHaveCount(1)
        ->and(collect(data_get($pageClassA, 'props.academicCalendarClass.students'))->pluck('studentEnrolmentId')->all())
        ->not->toContain($studentEnrolmentId);

    $showClassB = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classB->id,
    ]));

    $showClassB->assertSuccessful();
    $pageClassB = $showClassB->viewData('page');

    expect(data_get($pageClassB, 'props.academicCalendarClass.studentCount'))->toBe(2)
        ->and(data_get($pageClassB, 'props.academicCalendarClass.students'))->toHaveCount(2)
        ->and(collect(data_get($pageClassB, 'props.academicCalendarClass.students'))->pluck('studentEnrolmentId')->all())
        ->toContain($studentEnrolmentId);
});

test('user with student program permission but without viewAny academic calendars can move students', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'no-viewany-move-a@example.com');
    createFinalStudentProgram($context, 'no-viewany-move-b@example.com');
    createFinalStudentProgram($context, 'no-viewany-move-c@example.com');

    $context['user']->givePermissionTo('update:academic-calendar-student-enrolments');
    $context['user']->revokePermissionTo('viewAny:academic-calendars');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->get();

    $classA = $classes->first();
    $classB = $classes->last();

    $studentEnrolmentId = (int) DB::table('academic_calendar_student_enrolments')
        ->where('academic_calendar_class_id', $classA->id)
        ->whereNull('deleted_at')
        ->value('student_enrolment_id');

    expect($studentEnrolmentId)->toBeGreaterThan(0);

    $this->post(route('academic-calendars.department-classes.move-students', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]), [
        'student_enrolment_ids' => [$studentEnrolmentId],
        'target_academic_calendar_class_id' => $classB->id,
    ])->assertSessionHas('success');

    expect(
        (int) DB::table('academic_calendar_student_enrolments')
            ->where('student_enrolment_id', $studentEnrolmentId)
            ->whereNull('deleted_at')
            ->value('academic_calendar_class_id')
    )->toBe($classB->id);
});

test('moving students without permission is forbidden', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'forbid-a@example.com');
    createFinalStudentProgram($context, 'forbid-b@example.com');
    createFinalStudentProgram($context, 'forbid-c@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->get();

    $classA = $classes->first();
    $classB = $classes->last();

    $studentEnrolmentId = (int) DB::table('academic_calendar_student_enrolments')
        ->where('academic_calendar_class_id', $classA->id)
        ->whereNull('deleted_at')
        ->value('student_enrolment_id');

    $this->post(route('academic-calendars.department-classes.move-students', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]), [
        'student_enrolment_ids' => [$studentEnrolmentId],
        'target_academic_calendar_class_id' => $classB->id,
    ])->assertForbidden();
});

test('moving students validates target class and enrollment', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'val-a@example.com');
    createFinalStudentProgram($context, 'val-b@example.com');
    createFinalStudentProgram($context, 'val-c@example.com');

    $context['user']->givePermissionTo('update:academic-calendar-student-enrolments');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $classes = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->whereNull('deleted_at')
        ->orderBy('id')
        ->get();

    $classA = $classes->first();
    $classB = $classes->last();

    $studentEnrolmentIdOnA = (int) DB::table('academic_calendar_student_enrolments')
        ->where('academic_calendar_class_id', $classA->id)
        ->whereNull('deleted_at')
        ->value('student_enrolment_id');

    $studentEnrolmentIdOnB = (int) DB::table('academic_calendar_student_enrolments')
        ->where('academic_calendar_class_id', $classB->id)
        ->whereNull('deleted_at')
        ->value('student_enrolment_id');

    $moveUrl = route('academic-calendars.department-classes.move-students', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $classA->id,
    ]);

    $this->post($moveUrl, [
        'student_enrolment_ids' => [$studentEnrolmentIdOnA],
        'target_academic_calendar_class_id' => $classA->id,
    ])->assertSessionHasErrors('target_academic_calendar_class_id');

    $this->post($moveUrl, [
        'student_enrolment_ids' => [$studentEnrolmentIdOnB],
        'target_academic_calendar_class_id' => $classB->id,
    ])->assertSessionHasErrors('student_enrolment_ids');
});

test('authorized user can update academic calendar class name and description', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'patch-class-a@example.com');
    createFinalStudentProgram($context, 'patch-class-b@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $academicCalendarClass = AcademicCalendarClass::query()->firstOrFail();

    $updateUrl = route('academic-calendars.department-classes.update', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]);

    $this->patch($updateUrl, [
        'name' => 'Renamed class',
        'description' => 'Updated description line',
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $academicCalendarClass->refresh();

    expect($academicCalendarClass->name)->toBe('Renamed class')
        ->and($academicCalendarClass->description)->toBe('Updated description line');

    $show = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]));

    $show->assertSuccessful();
    $page = $show->viewData('page');

    expect(data_get($page, 'props.academicCalendarClass.name'))->toBe('Renamed class')
        ->and(data_get($page, 'props.academicCalendarClass.description'))->toBe('Updated description line')
        ->and(data_get($page, 'props.canUpdateAcademicCalendarClass'))->toBeTrue();
});

test('user without update academic calendar permission cannot update class', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'patch-forbidden-a@example.com');
    createFinalStudentProgram($context, 'patch-forbidden-b@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $context['user']->revokePermissionTo('update:academic-calendars');

    $academicCalendarClass = AcademicCalendarClass::query()->firstOrFail();

    $updateUrl = route('academic-calendars.department-classes.update', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]);

    $this->patch($updateUrl, [
        'name' => 'Should not apply',
        'description' => 'Nope',
    ])->assertForbidden();
});

test('updating class returns not found when institution department does not match class config', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'patch-404-a@example.com');
    createFinalStudentProgram($context, 'patch-404-b@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $academicCalendarClass = AcademicCalendarClass::query()->firstOrFail();

    $otherDepartment = Department::factory()->create();
    $otherInstitutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $context['tenant']->id,
        'department_id' => $otherDepartment->id,
        'department_code' => 'oth',
        'description' => 'Other department',
    ]);

    $updateUrl = route('academic-calendars.department-classes.update', [
        'institution_department' => $otherInstitutionDepartment->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]);

    $this->patch($updateUrl, [
        'name' => 'Wrong scope',
        'description' => null,
    ])->assertNotFound();
});

test('updating class validates name is required', function () {
    $context = buildDepartmentClassContext();
    createFinalStudentProgram($context, 'patch-val-a@example.com');
    createFinalStudentProgram($context, 'patch-val-b@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $academicCalendarClass = AcademicCalendarClass::query()->firstOrFail();
    $originalName = $academicCalendarClass->name;

    $updateUrl = route('academic-calendars.department-classes.update', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]);

    $this->patch($updateUrl, [
        'name' => '',
        'description' => 'Only description',
    ])->assertSessionHasErrors('name');

    $academicCalendarClass->refresh();

    expect($academicCalendarClass->name)->toBe($originalName);
});
