<?php

use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\AcademicCalendars\ClassConfig;
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
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;

if (! function_exists('createCourseWorkJsonApiContext')) {
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
            'name' => 'NC-FULL-TIME-1',
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

        $studentApplication = StudentApplication::query()->create([
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
            'student_id' => $studentApplication->student_id,
            'student_application_id' => $studentApplication->id,
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

}
