<?php

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Support\Collection;

test('per class size store rejects course syllabus from another department level course', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-syl',
        'description' => 'Per class size syllabus validation',
    ]);

    $courseA = Course::factory()->create();
    $departmentCourseA = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $courseA->id,
    ]);

    $courseB = Course::factory()->create();
    $departmentCourseB = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $courseB->id,
    ]);

    $levelA = Level::factory()->create();
    $departmentLevelA = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $levelA->id,
    ]);

    $levelB = Level::factory()->create();
    $departmentLevelB = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $levelB->id,
    ]);

    $dlcA = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourseA->id,
        'department_level_id' => $departmentLevelA->id,
    ]);

    $dlcB = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourseB->id,
        'department_level_id' => $departmentLevelB->id,
    ]);

    $syllabusOnA = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $dlcA->id,
        'title' => 'Syllabus on course A '.$departmentCourseA->id,
        'code' => 'SYL-A-'.$departmentCourseA->id,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $syllabusOnB = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $dlcB->id,
        'title' => 'Syllabus on course B '.$departmentCourseB->id,
        'code' => 'SYL-B-'.$departmentCourseB->id,
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time PCS']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $semester = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    $classConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'semester_id' => $semester->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourseA->id,
        'department_level_id' => $departmentLevelA->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 3,
    ]);

    $this->actingAs($user);

    $url = route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post($url, [
        'class_config_id' => $classConfig->id,
        'students_per_class' => 4,
        'department_level_id' => $departmentLevelA->id,
        'department_course_id' => $departmentCourseA->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semester->id,
        'course_syllabus_ids' => [$syllabusOnB->id],
    ])->assertSessionHasErrors(['course_syllabus_ids.0']);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post($url, [
        'class_config_id' => $classConfig->id,
        'students_per_class' => 4,
        'department_level_id' => $departmentLevelA->id,
        'department_course_id' => $departmentCourseA->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semester->id,
        'course_syllabus_ids' => [$syllabusOnA->id],
    ])->assertSessionHasNoErrors();

    $updated = ClassConfig::query()->where('department_course_id', $departmentCourseA->id)->first();
    expect($updated)->not->toBeNull()
        ->and((int) $updated->students_per_class)->toBe(4)
        ->and($updated->course_syllabus_ids)->toBe([$syllabusOnA->id]);
});

test('per class size store updates the existing config by id without inserting a second row', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-by-id',
        'description' => 'Update class config by id',
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

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time PCS By Id']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    $config = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'semester_id' => $semesterTwo->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 30,
    ]);

    $this->actingAs($user);

    $url = route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post($url, [
        'class_config_id' => $config->id,
        'students_per_class' => 18,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    expect(ClassConfig::query()->where('department_course_id', $departmentCourse->id)->count())->toBe(1);

    $updated = $config->fresh();
    expect($updated)->not->toBeNull()
        ->and((int) $updated->id)->toBe((int) $config->id)
        ->and((int) $updated->students_per_class)->toBe(18)
        ->and((int) $updated->semester_id)->toBe((int) $semesterOne->id);
});

test('per class size store can create semester one and semester two configs in the same year', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-two-semesters',
        'description' => 'Create two semester configs',
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

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Two Semesters']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    $this->actingAs($user);
    $url = route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]);
    $from = route('institution-departments.show', $institutionDepartment->id);

    $this->from($from)->post($url, [
        'students_per_class' => 20,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    $this->from($from)->post($url, [
        'students_per_class' => 22,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterTwo->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    expect(ClassConfig::query()
        ->where('department_course_id', $departmentCourse->id)
        ->where('calendar_year', '2026')
        ->count())->toBe(2);
});

test('per class size store rejects a duplicate period for the same year', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-duplicate',
        'description' => 'Duplicate semester config',
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

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Duplicate']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semesterOne->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 15,
    ]);

    $this->actingAs($user);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post(route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]), [
        'students_per_class' => 18,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id']);
});

test('per class size store rejects a third semester config for the same year', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-year-cap',
        'description' => 'Semester year cap',
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

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Year Cap']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $semesterOne->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 10,
    ]);
    ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => null,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 10,
    ]);

    $this->actingAs($user);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post(route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]), [
        'students_per_class' => 12,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterTwo->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id']);
});

test('per class size store rejects a fourth term config for the same year', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-term-cap',
        'description' => 'Term year cap',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['calendar_type' => AcademicCalendarTypeEnum::TERM]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Term Cap']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-04-30',
    ]);

    foreach ([1, 2, 3] as $number) {
        $term = Semester::query()->firstOrCreate(
            ['slug' => 'term-'.$number],
            ['name' => 'Term '.$number, 'description' => null],
        );
        ClassConfig::query()->create([
            'calendar_year' => '2026',
            'semester_id' => $term->id,
            'institution_department_id' => $institutionDepartment->id,
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $departmentLevel->id,
            'mode_of_study_id' => $modeOfStudy->id,
            'students_per_class' => 10 + $number,
        ]);
    }

    $termFour = Semester::query()->firstOrCreate(
        ['slug' => 'term-4'],
        ['name' => 'Term 4', 'description' => null],
    );

    $this->actingAs($user);

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post(route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]), [
        'students_per_class' => 16,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $termFour->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id']);
});

test('per class size store rejects a fifth abma config for the same year', function () {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-abma-cap',
        'description' => 'ABMA year cap',
    ]);

    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['calendar_type' => AcademicCalendarTypeEnum::ABMA]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time ABMA Cap']);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::ABMA,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-03-31',
    ]);

    foreach ([1, 2, 3, 4] as $number) {
        $period = Semester::query()->firstOrCreate(
            ['slug' => 'abma-'.$number],
            ['name' => 'ABMA '.$number, 'description' => null],
        );
        ClassConfig::query()->create([
            'calendar_year' => '2026',
            'semester_id' => $period->id,
            'institution_department_id' => $institutionDepartment->id,
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $departmentLevel->id,
            'mode_of_study_id' => $modeOfStudy->id,
            'students_per_class' => 10 + $number,
        ]);
    }

    $this->actingAs($user);

    $firstAbmaId = (int) Semester::query()->where('slug', 'abma-1')->value('id');

    $this->from(route('institution-departments.show', $institutionDepartment->id))->post(route('academic-calendars.classes-config.per-class-size.store', [
        'institution_department' => $institutionDepartment->id,
        'academic_calendar' => $calendar->id,
    ]), [
        'students_per_class' => 20,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $firstAbmaId,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id']);
});

test('per class size store accepts a programme semester id from the class config picker', function () {
    $context = buildProgrammeClassConfigStoreContext(2);
    $yearOneSemOne = $context['programmeSemesters']->first();

    expect($yearOneSemOne)->toBeInstanceOf(ProgrammeSemester::class)
        ->and($yearOneSemOne?->name)->toBe('Year 1 Sem 1');

    $this->actingAs($context['user']);

    $this->from(route('institution-departments.show', $context['institutionDepartment']->id))->post($context['storeUrl'], [
        'students_per_class' => 35,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearOneSemOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    $saved = ClassConfig::query()
        ->where('department_course_id', $context['departmentCourse']->id)
        ->first();

    expect($saved)->not->toBeNull()
        ->and((int) $saved->programme_semester_id)->toBe((int) $yearOneSemOne->id)
        ->and((int) $saved->semester_id)->toBe((int) $context['semesterOne']->id)
        ->and($saved->name)->toBe('Year 1 Sem 1')
        ->and((int) $saved->students_per_class)->toBe(35);
});

test('per class size store can create year 1 and year 2 configs that share a calendar semester', function () {
    $context = buildProgrammeClassConfigStoreContext(4);
    $yearOneSemOne = $context['programmeSemesters']->firstWhere('name', 'Year 1 Sem 1');
    $yearTwoSemOne = $context['programmeSemesters']->firstWhere('name', 'Year 2 Sem 1');

    expect($yearOneSemOne)->toBeInstanceOf(ProgrammeSemester::class)
        ->and($yearTwoSemOne)->toBeInstanceOf(ProgrammeSemester::class);

    $this->actingAs($context['user']);
    $from = route('institution-departments.show', $context['institutionDepartment']->id);

    $this->from($from)->post($context['storeUrl'], [
        'students_per_class' => 20,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearOneSemOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    $this->from($from)->post($context['storeUrl'], [
        'students_per_class' => 22,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearTwoSemOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    $configs = ClassConfig::query()
        ->where('department_course_id', $context['departmentCourse']->id)
        ->orderBy('id')
        ->get();

    expect($configs)->toHaveCount(2)
        ->and($configs->pluck('programme_semester_id')->map(fn ($id): int => (int) $id)->all())
        ->toEqualCanonicalizing([(int) $yearOneSemOne->id, (int) $yearTwoSemOne->id])
        ->and($configs->pluck('semester_id')->map(fn ($id): int => (int) $id)->unique()->all())
        ->toEqual([(int) $context['semesterOne']->id]);
});

test('per class size store rejects a duplicate programme semester for the same year', function () {
    $context = buildProgrammeClassConfigStoreContext(2);
    $yearOneSemOne = $context['programmeSemesters']->first();

    ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $context['semesterOne']->id,
        'programme_semester_id' => $yearOneSemOne->id,
        'name' => 'Year 1 Sem 1',
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 15,
    ]);

    $this->actingAs($context['user']);

    $this->from(route('institution-departments.show', $context['institutionDepartment']->id))->post($context['storeUrl'], [
        'students_per_class' => 18,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearOneSemOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id']);
});

test('per class size store rejects an industrial attachment period outside ojet', function () {
    $context = buildProgrammeClassConfigStoreContext(2, 2, ModeOfStudyEnum::FULL_TIME->value);
    $yearTwoAttachmentOne = $context['programmeSemesters']->firstWhere('name', 'Year 2 Attachment 1');

    expect($yearTwoAttachmentOne)->toBeInstanceOf(ProgrammeSemester::class);

    $this->actingAs($context['user']);

    $this->from(route('institution-departments.show', $context['institutionDepartment']->id))->post($context['storeUrl'], [
        'students_per_class' => 30,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearTwoAttachmentOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id' => __('academic_calendar.class_config_period_not_offered_in_mode')]);

    expect(ClassConfig::query()->where('department_course_id', $context['departmentCourse']->id)->exists())->toBeFalse();
});

test('per class size store rejects a taught period under ojet', function () {
    $context = buildProgrammeClassConfigStoreContext(2, 2, ModeOfStudyEnum::OJET->value);
    $yearOneSemOne = $context['programmeSemesters']->firstWhere('name', 'Year 1 Sem 1');

    $this->actingAs($context['user']);

    $this->from(route('institution-departments.show', $context['institutionDepartment']->id))->post($context['storeUrl'], [
        'students_per_class' => 30,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearOneSemOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasErrors(['semester_id' => __('academic_calendar.class_config_period_not_offered_in_mode')]);
});

test('per class size store accepts an industrial attachment period under ojet', function () {
    $context = buildProgrammeClassConfigStoreContext(2, 2, ModeOfStudyEnum::OJET->value);
    $yearTwoAttachmentOne = $context['programmeSemesters']->firstWhere('name', 'Year 2 Attachment 1');

    $this->actingAs($context['user']);

    $this->from(route('institution-departments.show', $context['institutionDepartment']->id))->post($context['storeUrl'], [
        'students_per_class' => 30,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearTwoAttachmentOne->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    $saved = ClassConfig::query()
        ->where('department_course_id', $context['departmentCourse']->id)
        ->first();

    expect($saved)->not->toBeNull()
        ->and((int) $saved->programme_semester_id)->toBe((int) $yearTwoAttachmentOne->id)
        ->and($saved->name)->toBe('Year 2 Attachment 1');
});

// Configs saved before the mode rule existed must stay editable while they keep their period.
test('per class size store still updates a config that keeps a period stored before the mode rule', function () {
    $context = buildProgrammeClassConfigStoreContext(2, 2, ModeOfStudyEnum::FULL_TIME->value);
    $yearTwoAttachmentOne = $context['programmeSemesters']->firstWhere('name', 'Year 2 Attachment 1');

    $existing = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'semester_id' => $context['semesterOne']->id,
        'programme_semester_id' => $yearTwoAttachmentOne->id,
        'name' => 'Year 2 Attachment 1',
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 15,
    ]);

    $this->actingAs($context['user']);

    $this->from(route('institution-departments.show', $context['institutionDepartment']->id))->post($context['storeUrl'], [
        'students_per_class' => 25,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'semester_id' => $yearTwoAttachmentOne->id,
        'class_config_id' => $existing->id,
        'course_syllabus_ids' => [],
    ])->assertSessionHasNoErrors();

    expect((int) $existing->fresh()->students_per_class)->toBe(25);
});

/**
 * @return array{
 *     user: User,
 *     institutionDepartment: InstitutionDepartment,
 *     departmentCourse: DepartmentCourse,
 *     departmentLevel: DepartmentLevel,
 *     modeOfStudy: ModeOfStudy,
 *     calendar: AcademicCalendar,
 *     semesterOne: Semester,
 *     semesterTwo: Semester,
 *     programmeSemesters: Collection<int, ProgrammeSemester>,
 *     storeUrl: string
 * }
 */
function buildProgrammeClassConfigStoreContext(
    int $taughtSemesterCount = 2,
    int $attachmentSemesterCount = 0,
    ?string $modeOfStudyName = null,
): array {
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo(['viewAny:academic-calendars', 'update:academic-calendars']);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pcs-prog-'.$taughtSemesterCount.'-'.$attachmentSemesterCount,
        'description' => 'Programme semester class config',
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

    $dlc = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'duration_years' => (int) ceil(($taughtSemesterCount + $attachmentSemesterCount) / 2),
        'taught_semester_count' => $taughtSemesterCount,
        'includes_industrial_attachment' => $attachmentSemesterCount > 0,
        'attachment_semester_count' => $attachmentSemesterCount,
    ]);

    $programmeSemesters = app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc->fresh() ?? $dlc);

    $modeOfStudy = ModeOfStudy::query()->firstOrCreate([
        'name' => $modeOfStudyName ?? 'Full Time Programme Config '.$taughtSemesterCount,
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-06-30',
    ]);
    $semesterOne = Semester::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    return [
        'user' => $user,
        'institutionDepartment' => $institutionDepartment,
        'departmentCourse' => $departmentCourse,
        'departmentLevel' => $departmentLevel,
        'modeOfStudy' => $modeOfStudy,
        'calendar' => $calendar,
        'semesterOne' => $semesterOne,
        'semesterTwo' => $semesterTwo,
        'programmeSemesters' => $programmeSemesters,
        'storeUrl' => route('academic-calendars.classes-config.per-class-size.store', [
            'institution_department' => $institutionDepartment->id,
            'academic_calendar' => $calendar->id,
        ]),
    ];
}
