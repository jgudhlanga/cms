<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use App\Services\Dashboard\AcademicDashboardMetricsService;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    enableDashboardModule();
    $this->seed(ClassMetaDataTypeSeeder::class);
});

it('preloads class configs in a single query during academic dashboard build', function (): void {
    $context = createCourseWorkJsonApiContext();

    $context['assessmentType']->update(['weight_percent' => 100]);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 45,
    ]);

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);

    request()->merge(['academic_calendar_id' => $calendarId]);

    $classConfigQueries = 0;

    Event::listen(QueryExecuted::class, function (QueryExecuted $query) use (&$classConfigQueries): void {
        $sql = strtolower($query->sql);
        if (str_contains($sql, 'from `class_configs`') || str_contains($sql, 'from "class_configs"')) {
            $classConfigQueries++;
        }
    });

    $payload = app(AcademicDashboardMetricsService::class)->build();

    expect($classConfigQueries)->toBeLessThanOrEqual(2)
        ->and($payload)->toHaveKey('atRiskStudentCount')
        ->and($payload['summary']['passRate'])->toEqual(100);
});

it('includes atRiskStudentCount on academic dashboard build payload', function (): void {
    $context = createCourseWorkJsonApiContext();

    $context['assessmentType']->update(['weight_percent' => 100]);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 10,
    ]);

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);
    request()->merge(['academic_calendar_id' => $calendarId]);

    $service = app(AcademicDashboardMetricsService::class);
    $payload = $service->build();

    expect($payload['atRiskStudentCount'])->toBe($service->atRiskStudentCount())
        ->and($payload['summary']['failRate'])->toEqual(100);
});

it('does not query class_configs once per enrolment when multiple enrolments share a config', function (): void {
    $context = createCourseWorkJsonApiContext();
    $suffix = uniqid();

    $studentUser = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    $student = Student::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $studentUser->id,
        'title_id' => Title::query()->create(['name' => 'Mr CW2 '.$suffix])->id,
        'gender_id' => Gender::query()->create(['title' => 'Male CW2 '.$suffix])->id,
        'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single CW2 '.$suffix])->id,
        'id_type_id' => IdType::query()->create(['name' => 'ID CW2 '.$suffix])->id,
        'date_of_birth' => '2001-01-01',
    ]);

    $application = StudentApplication::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_id' => $student->id,
        'institution_department_id' => $context['studentEnrolment']->institution_department_id,
        'department_level_id' => $context['studentEnrolment']->department_level_id,
        'department_course_id' => $context['studentEnrolment']->department_course_id,
        'intake_period_id' => $context['studentEnrolment']->studentApplication->intake_period_id,
        'mode_of_study_id' => $context['studentEnrolment']->mode_of_study_id,
        'application_tracking_number' => 'APP-CW2-'.$suffix,
    ]);

    $secondEnrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $application->id,
        'institution_department_id' => $context['studentEnrolment']->institution_department_id,
        'department_level_id' => $context['studentEnrolment']->department_level_id,
        'department_course_id' => $context['studentEnrolment']->department_course_id,
        'academic_year_option_id' => $context['studentEnrolment']->academic_year_option_id,
        'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
        'mode_of_study_id' => $context['studentEnrolment']->mode_of_study_id,
        'student_enrolment_status_id' => $context['studentEnrolment']->student_enrolment_status_id,
    ]);

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'student_enrolment_id' => $secondEnrolment->id,
    ]);

    AcademicCalendar::query()->whereKey($context['studentEnrolment']->academic_calendar_id)->update([
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ]);
    request()->merge(['academic_calendar_id' => (int) $context['studentEnrolment']->academic_calendar_id]);

    $classConfigSelects = 0;
    DB::listen(function (QueryExecuted $query) use (&$classConfigSelects): void {
        $sql = strtolower($query->sql);
        if (
            (str_contains($sql, 'from `class_configs`') || str_contains($sql, 'from "class_configs"'))
            && str_starts_with(trim($sql), 'select')
        ) {
            $classConfigSelects++;
        }
    });

    app(AcademicDashboardMetricsService::class)->build();

    expect($classConfigSelects)->toBeLessThanOrEqual(2)
        ->and(ClassConfig::query()->count())->toBeGreaterThanOrEqual(1);
});
