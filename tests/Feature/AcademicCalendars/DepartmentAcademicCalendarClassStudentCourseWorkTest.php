<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\Acl\Permission;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
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

test('authorized user can view student course work page', function () {
    $context = createStudentCourseWorkPageContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.student-course-work', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'student_enrolment' => $context['studentEnrolment']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/academicCalendars/DepartmentAcademicCalendarClassStudentCourseWork')
            ->has('student')
            ->where('student.studentEnrolmentId', $context['studentEnrolment']->id));
});

test('unauthorized user cannot view student course work page', function () {
    $context = createStudentCourseWorkPageContext();

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.student-course-work', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'student_enrolment' => $context['studentEnrolment']->id,
        ]))
        ->assertForbidden();
});

test('student course work page returns 404 when enrolment not in class', function () {
    $context = createStudentCourseWorkPageContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');

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

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.student-course-work', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'student_enrolment' => $otherEnrolment->id,
        ]))
        ->assertNotFound();
});

/**
 * @return array<string, mixed>
 */
function createStudentCourseWorkPageContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $department = Department::factory()->create(['name' => 'ICT CW Page '.uniqid()]);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'CW-PAGE-'.uniqid(),
        'description' => 'Course work page test',
    ]);

    $course = Course::factory()->create(['name' => 'IT CW Page']);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $level = Level::factory()->create(['name' => 'NC CW Page']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time CW Page']);
    $academicYearOption = AcademicYearOption::query()->create([
        'name' => 'Semester 1 CW Page',
        'description' => null,
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'opening_date' => now()->subDays(30)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    $classConfig = ClassConfig::query()->create([
        'calendar_year' => $calendar->calendar_year,
        'academic_year_option_id' => $academicYearOption->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'students_per_class' => 10,
        'course_syllabus_ids' => [],
    ]);

    $academicCalendarClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $tenant->id,
        'class_config_id' => $classConfig->id,
        'name' => 'NC-FULL-TIME-CW-PAGE',
        'description' => null,
    ]);

    $suffix = uniqid();
    $studentUser = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::query()->create([
        'tenant_id' => $tenant->id,
        'user_id' => $studentUser->id,
        'title_id' => Title::query()->create(['name' => 'Mr CW Page '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male CW Page '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single CW Page '.$suffix])->id,
        'id_type_id' => IdType::query()->create(['name' => 'ID CW Page '.$suffix])->id,
        'date_of_birth' => '2001-01-01',
    ]);

    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Intake CW Page',
        'calendar_year' => '2026',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $studentProgram = StudentProgram::query()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-CW-PAGE-'.uniqid(),
    ]);

    $enrolmentStatus = StudentEnrolmentStatus::query()->create([
        'name' => 'Active CW Page',
        'description' => 'Test',
    ]);

    $studentEnrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
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

    return compact(
        'tenant',
        'user',
        'institutionDepartment',
        'calendar',
        'academicCalendarClass',
        'studentEnrolment',
    );
}
