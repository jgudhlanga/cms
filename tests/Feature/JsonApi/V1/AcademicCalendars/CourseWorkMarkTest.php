<?php

use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
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
use App\Support\Acl\PermissionRegistry;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

/**
 * @return array<string, mixed>
 */
function createCourseWorkJsonApiContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create(['name' => 'ICT Course Work '.uniqid()]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'CW-'.uniqid(),
        'description' => 'Course work test',
    ]);

    $course = Course::factory()->create(['name' => 'Information Technology '.uniqid()]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'NC '.uniqid()]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'tenant_id' => $tenant->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time CW '.uniqid()]);
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Semester 1 CW',
        'description' => null,
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->subDays(30)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    $syllabus = CourseSyllabus::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'IT Syllabus',
        'code' => 'IT-SYL',
        'implementation_year' => '2026',
        'status' => CourseSyllabusStatusEnum::Active,
    ]);

    $module = CourseSyllabusModule::query()->create([
        'tenant_id' => $tenant->id,
        'course_syllabus_id' => $syllabus->id,
        'academic_year_option_id' => $academicYearOption->id,
        'title' => 'Networking',
        'code' => 'NET101',
        'duration_in_hours' => 40,
    ]);

    $classConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'academic_year_option_id' => $academicYearOption->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 10,
        'course_syllabus_ids' => [$syllabus->id],
    ]);

    $academicCalendarClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $tenant->id,
        'class_config_id' => $classConfig->id,
        'name' => 'NC-FULL-TIME-1-8',
        'description' => null,
    ]);

    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Intake CW',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $suffix = uniqid();
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => Title::query()->create(['name' => 'Mr CW '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male CW '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single CW '.$suffix])->id,
        'id_type_id' => IdType::query()->create(['name' => 'ID CW '.$suffix])->id,
        'date_of_birth' => '2001-01-01',
    ]);

    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CW-'.uniqid(),
    ]);

    $enrolmentStatus = StudentEnrolmentStatus::query()->create([
        'name' => 'Active CW',
        'description' => 'Test',
    ]);

    $studentEnrolment = StudentEnrolment::query()->create([
        'student_id' => $studentProgram->student_id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'student_enrolment_status_id' => $enrolmentStatus->id,
    ]);

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $tenant->id,
        'academic_calendar_class_id' => $academicCalendarClass->id,
        'student_enrolment_id' => $studentEnrolment->id,
    ]);

    $assessmentType = AssessmentType::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Test Assessment '.uniqid(),
        'modes_of_study' => [$modeOfStudy->id],
    ]);

    return compact(
        'tenant',
        'user',
        'academicCalendarClass',
        'studentEnrolment',
        'module',
        'assessmentType',
        'modeOfStudy',
    );
}

test('json api course work tree requires permission', function () {
    $context = createCourseWorkJsonApiContext();
    Sanctum::actingAs($context['user']);

    $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertForbidden();
});

test('json api course work tree returns syllabi modules and students', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.academicCalendarClassId', $context['academicCalendarClass']->id)
        ->assertJsonPath('meta.syllabi.0.modules.0.id', $context['module']->id)
        ->assertJsonPath('meta.syllabi.0.modules.0.students.0.studentEnrolmentId', $context['studentEnrolment']->id)
        ->assertJsonPath('meta.assessmentTypes.0.id', $context['assessmentType']->id);
});

test('json api course work store creates mark and audit log', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 72,
                'remark' => 'Good work',
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]));

    $response->assertCreated()
        ->assertJsonPath('data.attributes.mark', 72)
        ->assertJsonPath('data.attributes.remark', 'Good work');

    $mark = CourseWorkMark::query()->first();
    expect($mark)->not->toBeNull()
        ->and($mark->mark)->toBe(72);

    expect(CourseWorkAuditLog::query()->where('course_work_mark_id', $mark->id)->count())->toBe(1);
});

test('json api course work store rejects marks above 100', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');
    Sanctum::actingAs($context['user']);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 101,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store', ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertStatus(422);
});

test('json api course work store rejects decimal marks', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo('create:course-work');
    Sanctum::actingAs($context['user']);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'attributes' => [
                'studentEnrolmentId' => $context['studentEnrolment']->id,
                'courseSyllabusModuleId' => $context['module']->id,
                'assessmentTypeId' => $context['assessmentType']->id,
                'mark' => 72.5,
            ],
        ])
        ->post(route('v1.json.course-work-marks.store'))
        ->assertStatus(422);
});

test('json api course work update writes audit log', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('create:course-work', 'web');
    Permission::findOrCreate('update:course-work', 'web');
    $context['user']->givePermissionTo(['create:course-work', 'update:course-work']);
    Sanctum::actingAs($context['user']);

    $mark = CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 50,
        'created_by' => $context['user']->id,
        'updated_by' => $context['user']->id,
    ]);

    $this->jsonApi('course-work-marks')
        ->withData([
            'type' => 'course-work-marks',
            'id' => (string) $mark->id,
            'attributes' => [
                'mark' => 88,
                'remark' => 'Improved',
            ],
        ])
        ->patch(route('v1.json.course-work-marks.update', $mark->id, ['filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id]]))
        ->assertSuccessful();

    expect($mark->refresh()->mark)->toBe(88);
    expect(CourseWorkAuditLog::query()->where('course_work_mark_id', $mark->id)->where('event', 'updated')->count())->toBe(1);
});

test('json api course work student tree returns modules with assessments only', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $response = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => [
                'academicCalendarClass' => $context['academicCalendarClass']->id,
                'studentEnrolment' => $context['studentEnrolment']->id,
            ],
        ]));

    $response->assertSuccessful()
        ->assertJsonPath('meta.studentEnrolmentId', $context['studentEnrolment']->id)
        ->assertJsonPath('meta.syllabi.0.modules.0.assessments.0.assessmentTypeId', $context['assessmentType']->id)
        ->assertJsonMissingPath('meta.syllabi.0.modules.0.students');
});

test('json api course work student tree rejects enrolment not in class', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');
    Sanctum::actingAs($context['user']);

    $otherEnrolment = StudentEnrolment::query()->create([
        'student_id' => $context['studentEnrolment']->student_id,
        'student_program_id' => $context['studentEnrolment']->student_program_id,
        'institution_department_id' => $context['studentEnrolment']->institution_department_id,
        'department_level_id' => $context['studentEnrolment']->department_level_id,
        'department_course_id' => $context['studentEnrolment']->department_course_id,
        'academic_year_option_id' => $context['studentEnrolment']->academic_year_option_id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'mode_of_study_id' => $context['studentEnrolment']->mode_of_study_id,
        'student_enrolment_status_id' => $context['studentEnrolment']->student_enrolment_status_id,
    ]);

    $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => [
                'academicCalendarClass' => $context['academicCalendarClass']->id,
                'studentEnrolment' => $otherEnrolment->id,
            ],
        ]))
        ->assertStatus(422);
});

test('permission registry resolves course work module title', function () {
    expect(PermissionRegistry::moduleTitleForGroupKey('course-work'))->toBe('Course Work');
});
