<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Enums\Institution\CourseSyllabusStatusEnum;
use App\Enums\Rbac\RoleEnum;
use App\Exports\AcademicCalendars\CourseWorkImportTemplateExport;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
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
use App\Models\Rbac\Role;
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
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

if (! function_exists('createCourseWorkJsonApiContext')) {
    /**
     * @return array<string, mixed>
     */
    function createCourseWorkJsonApiContext(): array
    {
        $tenant = Tenant::query()->firstOrFail();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Permission::findOrCreate('viewAny:academic-calendars', 'web');
        $user->givePermissionTo('viewAny:academic-calendars');

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
        $semester = Semester::query()->create([
            'name' => 'Semester 1 CW',
            'description' => null,
        ]);

        $calendar = AcademicCalendar::query()->create([
            'calendar_year' => '2026',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
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
            'semester_id' => $semester->id,
            'title' => 'Networking',
            'code' => 'NET101',
            'duration_in_hours' => 40,
        ]);

        $classConfig = ClassConfig::query()->create([
            'calendar_year' => $calendar->calendar_year,
            'semester_id' => $semester->id,
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
            'semester_id' => $semester->id,
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
            'institutionDepartment',
            'classConfig',
            'academicCalendarClass',
            'studentEnrolment',
            'module',
            'assessmentType',
            'modeOfStudy',
            'calendar',
            'departmentCourse',
            'student',
            'studentUser',
        );
    }
}

if (! function_exists('grantCourseWorkLifecyclePermissions')) {
    /**
     * @param  list<string>  $permissions
     */
    function grantCourseWorkLifecyclePermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
    }
}

if (! function_exists('createCourseWorkLifecycleActors')) {
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    function createCourseWorkLifecycleActors(array $context): array
    {
        $admin = $context['user'];
        grantCourseWorkLifecyclePermissions($admin, [
            'viewAny:assessment-types',
            'create:assessment-types',
            'update:assessment-types',
            'viewAny:assessment-calendar',
            'create:assessment-calendar',
            'update:assessment-calendar',
            'viewAny:academic-calendars',
            'view:academic-calendars',
            'viewAny:course-work',
            'view:course-work',
            'create:course-work',
            'update:course-work',
            'delete:course-work',
            'import:course-work',
            'export:course-work',
            'viewAuditTrail:course-work',
            'toggle:coursework-capture',
        ]);

        [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
        grantCourseWorkLifecyclePermissions($lecturerUser, [
            'delete:course-work',
            'view:lecturer-classes',
            'view:lecturer-modules',
        ]);
        assignLecturerToClassModule($context, $staff);

        $vp = User::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'first_name' => 'Vice',
            'last_name' => 'Principal',
        ]);
        $vp->assignRole(Role::query()->where('name', RoleEnum::VICE_PRINCIPAL->name())->firstOrFail());
        grantCourseWorkLifecyclePermissions($vp, [
            'view:missing-marks-report',
            'export:missing-marks-report',
            'escalate:missing-marks',
            'remind:missing-marks',
        ]);

        $principal = User::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'first_name' => 'Principal',
            'last_name' => 'User',
        ]);
        $principal->assignRole(Role::query()->where('name', RoleEnum::PRINCIPAL->name())->firstOrFail());

        return [
            ...$context,
            'admin' => $admin,
            'lecturerUser' => $lecturerUser,
            'staff' => $staff,
            'vp' => $vp,
            'principal' => $principal,
        ];
    }
}

if (! function_exists('storeAssessmentTypeViaHttp')) {
    /**
     * @param  array{name: string, modes_of_study: list<int>, description?: string|null}  $payload
     */
    function storeAssessmentTypeViaHttp(User $user, array $payload): AssessmentType
    {
        test()->actingAs($user)
            ->post(route('assessment-types.store'), $payload)
            ->assertSuccessful();

        return AssessmentType::query()
            ->where('name', $payload['name'])
            ->latest('id')
            ->firstOrFail();
    }
}

if (! function_exists('storeAssessmentCalendarViaHttp')) {
    /**
     * @param  array<string, mixed>  $payload
     */
    function storeAssessmentCalendarViaHttp(User $user, AssessmentType $type, array $payload): AssessmentCalendar
    {
        test()->actingAs($user)
            ->post(route('assessment-calendars.store', ['assessment_type' => $type->id]), $payload)
            ->assertSuccessful();

        return AssessmentCalendar::query()
            ->where('assessment_type_id', $type->id)
            ->where('academic_calendar_id', $payload['academic_calendar_id'])
            ->latest('id')
            ->firstOrFail();
    }
}

if (! function_exists('jsonApiStoreCourseWorkMark')) {
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $query
     */
    function jsonApiStoreCourseWorkMark(User $user, array $context, array $attributes, array $query = []): TestResponse
    {
        Sanctum::actingAs($user);

        $filter = $query['filter'] ?? [
            'academicCalendarClass' => $context['academicCalendarClass']->id,
        ];

        $routeParams = array_filter([
            'filter' => $filter,
            'academic_calendar_id' => $query['academic_calendar_id']
                ?? $context['studentEnrolment']->academic_calendar_id
                ?? null,
        ], static fn (mixed $value): bool => $value !== null);

        return test()->jsonApi('course-work-marks')
            ->withData([
                'type' => 'course-work-marks',
                'attributes' => $attributes,
            ])
            ->post(route('v1.json.course-work-marks.store', $routeParams));
    }
}

if (! function_exists('jsonApiDestroyCourseWorkMark')) {
    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $query
     */
    function jsonApiDestroyCourseWorkMark(User $user, int $markId, array $context, array $query = []): TestResponse
    {
        Sanctum::actingAs($user);

        $filter = $query['filter'] ?? [
            'academicCalendarClass' => $context['academicCalendarClass']->id,
        ];

        return test()->jsonApi('course-work-marks')
            ->delete(route('v1.json.course-work-marks.destroy', [
                'course_work_mark' => $markId,
                'filter' => $filter,
                'academic_calendar_id' => $query['academic_calendar_id']
                    ?? $context['studentEnrolment']->academic_calendar_id,
            ]));
    }
}

if (! function_exists('travelToNotificationTier')) {
    function travelToNotificationTier(AssessmentCalendar $calendar, MissingMarksNotificationTierEnum $tier): Carbon
    {
        $date = $calendar->notificationDate($calendar->daysBeforeFor($tier));

        if (! $date instanceof Carbon) {
            throw new RuntimeException('Assessment calendar has no end date for notification travel.');
        }

        $at = $date->copy()->startOfDay()->addHours(8);
        Carbon::setTestNow($at);

        return $at;
    }
}

if (! function_exists('courseWorkImportRouteParams')) {
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    function courseWorkImportRouteParams(array $context): array
    {
        $classConfig = $context['academicCalendarClass']->classConfig
            ?? $context['classConfig'];

        return [
            'institution_department' => $classConfig->institution_department_id,
            'calendar_year' => $classConfig->calendar_year,
            'class_config_id' => $classConfig->id,
            'department_course_id' => $classConfig->department_course_id,
            'department_level_id' => $classConfig->department_level_id,
            'mode_of_study_id' => $classConfig->mode_of_study_id,
        ];
    }
}

if (! function_exists('setCourseWorkWideImportMark')) {
    /**
     * @param  array<string, mixed>  $data
     */
    function setCourseWorkWideImportMark(array &$data, int $assessmentTypeId, mixed $mark, int $studentRowIndex = 0): void
    {
        $data['rows'][$studentRowIndex]['marks'][$assessmentTypeId] = $mark;
    }
}

if (! function_exists('storeCourseWorkImportFile')) {
    /**
     * @param  array<string, mixed>  $data
     */
    function storeCourseWorkImportFile(array $data): UploadedFile
    {
        $relativePath = 'test-course-work-import-'.uniqid().'.xlsx';
        Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');

        return new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);
    }
}

if (! function_exists('courseWorkImportPreviewAndProcess')) {
    /**
     * @param  array<string, mixed>  $context
     */
    function courseWorkImportPreviewAndProcess(UploadedFile $file, array $context, int $moduleId, ?User $asUser = null): void
    {
        $user = $asUser ?? $context['admin'] ?? $context['user'];
        test()->actingAs($user);

        $previewResponse = test()->post(route(
            'academic-calendars.department-classes.course-work-import.preview',
            courseWorkImportRouteParams($context),
        ), [
            'module' => $moduleId,
            'file' => $file,
        ]);

        $previewResponse->assertSuccessful();

        $previewToken = $previewResponse->json('previewToken');
        expect($previewToken)->not->toBeEmpty();

        test()->post(route(
            'academic-calendars.department-classes.course-work-import.process',
            courseWorkImportRouteParams($context),
        ), [
            'module' => $moduleId,
            'preview_token' => $previewToken,
        ])->assertRedirect();
    }
}

if (! function_exists('courseWorkMarksheetRouteParams')) {
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    function courseWorkMarksheetRouteParams(array $context): array
    {
        return courseWorkImportRouteParams($context);
    }
}
