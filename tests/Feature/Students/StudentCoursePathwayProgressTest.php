<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Institution\Course;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\Level;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentCoursePathwayProgressService;
use App\Services\Students\StudentProgrammeDataService;
use Illuminate\Support\Str;

require_once __DIR__.'/../../Support/BulkFinaliseTestHelpers.php';

beforeEach(function (): void {
    foreach (['Active', 'Award', 'Proceed', 'Referred'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }

    Semester::query()->firstOrCreate(['slug' => 'semester-1'], ['name' => 'Semester 1']);
    Semester::query()->firstOrCreate(['slug' => 'semester-2'], ['name' => 'Semester 2']);
});

if (! function_exists('createItPathwayContext')) {
    /**
     * @return array{
     *     student: Student,
     *     departmentCourse: DepartmentCourse,
     *     nc: DepartmentLevelCourse,
     *     nd: DepartmentLevelCourse,
     *     hnd: DepartmentLevelCourse,
     *     ndApplication: StudentApplication
     * }
     */
    function createItPathwayContext(bool $ndRequiresNc, bool $syncStructure = true): array
    {
        $ndApplication = createVerifiedStudentApplication('PATH-'.Str::upper(Str::random(4)));
        $student = $ndApplication->student;
        $tenantId = (int) $ndApplication->tenant_id;
        $departmentCourse = $ndApplication->departmentCourse;
        $institutionDepartment = $ndApplication->institutionDepartment;

        $ncLevel = Level::factory()->create([
            'name' => 'NC',
            'position' => 1,
            'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
        ]);
        $ndLevel = $ndApplication->departmentLevel->level;
        $ndLevel->update([
            'name' => 'ND',
            'position' => 2,
            'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
        ]);
        $hndLevel = Level::factory()->create([
            'name' => 'HND',
            'position' => 3,
            'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
        ]);

        $ncDepartmentLevel = DepartmentLevel::query()->create([
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
            'level_id' => $ncLevel->id,
        ]);

        $hndDepartmentLevel = DepartmentLevel::query()->create([
            'tenant_id' => $tenantId,
            'institution_department_id' => $institutionDepartment->id,
            'level_id' => $hndLevel->id,
        ]);

        $existingDlc = DepartmentLevelCourse::query()
            ->where('department_course_id', $departmentCourse->id)
            ->where('department_level_id', $ndApplication->department_level_id)
            ->firstOrFail();

        $nc = DepartmentLevelCourse::query()->create([
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $ncDepartmentLevel->id,
            'duration_years' => 1,
            'taught_semester_count' => 2,
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ]);

        $existingDlc->update([
            'duration_years' => 1,
            'taught_semester_count' => 2,
            'includes_industrial_attachment' => true,
            'attachment_semester_count' => 2,
        ]);
        $nd = $existingDlc->fresh() ?? $existingDlc;

        $hnd = DepartmentLevelCourse::query()->create([
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $hndDepartmentLevel->id,
            'duration_years' => 1,
            'taught_semester_count' => 2,
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ]);

        if ($syncStructure) {
            $sync = app(SyncProgrammeSemestersForOfferingAction::class);
            $sync->execute($nc);
            $sync->execute($nd);
            $sync->execute($hnd);
        }

        if ($ndRequiresNc) {
            ApplicationCourseRequirement::query()->create([
                'tenant_id' => $tenantId,
                'department_level_id' => $nd->department_level_id,
                'department_course_id' => $departmentCourse->id,
                'is_o_level_required' => false,
                'required_subjects_count' => 0,
                'main_subjects_count' => 0,
                'main_subject_ids' => [],
                'other_subjects_count' => 0,
                'only_read_write_required' => false,
                'required_level_id' => $ncLevel->id,
            ]);
        }

        enrolStudentOnPathwayApplication($ndApplication);

        return [
            'student' => $student->fresh(['applications', 'enrolments.studentSemesters.studentEnrolmentStatus']) ?? $student,
            'departmentCourse' => $departmentCourse,
            'nc' => $nc->fresh(['programmeSemesters', 'departmentLevel.level']) ?? $nc,
            'nd' => $nd->fresh(['programmeSemesters', 'departmentLevel.level']) ?? $nd,
            'hnd' => $hnd->fresh(['programmeSemesters', 'departmentLevel.level']) ?? $hnd,
            'ndApplication' => $ndApplication,
        ];
    }
}

if (! function_exists('enrolStudentOnPathwayApplication')) {
    function enrolStudentOnPathwayApplication(StudentApplication $application, string $statusSlug = 'active'): StudentEnrolment
    {
        $calendar = AcademicCalendar::query()->firstOrCreate(
            [
                'calendar_year' => '2026',
                'type' => AcademicCalendarTypeEnum::SEMESTER->value,
                'opening_date' => '2026-01-01',
            ],
            ['closing_date' => '2026-12-31'],
        );

        $statusId = (int) StudentEnrolmentStatus::query()->where('slug', $statusSlug)->value('id');
        $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');

        return StudentEnrolment::query()->create([
            'student_id' => $application->student_id,
            'student_application_id' => $application->id,
            'institution_department_id' => $application->institution_department_id,
            'department_level_id' => $application->department_level_id,
            'department_course_id' => $application->department_course_id,
            'semester_id' => $semesterId,
            'academic_calendar_id' => $calendar->id,
            'mode_of_study_id' => $application->mode_of_study_id,
            'student_enrolment_status_id' => $statusId,
        ]);
    }
}

it('marks required prior NC as implied complete when the student is only on ND', function (): void {
    $context = createItPathwayContext(ndRequiresNc: true);
    $pathways = app(StudentCoursePathwayProgressService::class)->buildForStudent($context['student']);

    expect($pathways)->toHaveCount(1);

    $stages = collect($pathways[0]['stages']);

    expect($stages)->toHaveCount(3)
        ->and($stages->firstWhere('levelName', 'NC')['impliedComplete'])->toBeTrue()
        ->and($stages->firstWhere('levelName', 'NC')['status'])->toBe('completed')
        ->and($stages->firstWhere('levelName', 'ND')['status'])->toBe('current')
        ->and($stages->firstWhere('levelName', 'HND')['status'])->toBe('locked')
        ->and($stages->firstWhere('levelName', 'HND')['impliedComplete'])->toBeFalse()
        ->and($pathways[0]['stepsTotal'])->toBe(8)
        ->and($pathways[0]['yearsTotal'])->toBe(4.0);
});

it('omits NC when ND does not require it', function (): void {
    $context = createItPathwayContext(ndRequiresNc: false);
    $pathways = app(StudentCoursePathwayProgressService::class)->buildForStudent($context['student']);

    $stageNames = collect($pathways[0]['stages'])->pluck('levelName')->all();

    expect($stageNames)->toBe(['ND', 'HND']);
});

it('does not invent ticks when programme structure is missing', function (): void {
    $context = createItPathwayContext(ndRequiresNc: true, syncStructure: false);
    $pathways = app(StudentCoursePathwayProgressService::class)->buildForStudent($context['student']);

    $nc = collect($pathways[0]['stages'])->firstWhere('levelName', 'NC');
    $nd = collect($pathways[0]['stages'])->firstWhere('levelName', 'ND');

    expect($nc['steps'])->toBe([])
        ->and($nc['structureMissing'])->toBeTrue()
        ->and($nd['steps'])->toBe([])
        ->and($nd['structureMissing'])->toBeTrue()
        ->and($pathways[0]['stepsTotal'])->toBe(0)
        ->and($pathways[0]['yearsTotal'])->toBe(4.0);
});

it('keeps a referred student on the blocked tick', function (): void {
    $context = createItPathwayContext(ndRequiresNc: true);
    $student = $context['student'];
    $referredId = (int) StudentEnrolmentStatus::query()->where('slug', 'referred')->value('id');
    $firstProgrammeSemesterId = (int) $context['nd']->programmeSemesters->sortBy('position')->first()?->id;
    $semesterId = (int) Semester::query()->where('slug', 'semester-1')->value('id');

    $enrolment = StudentEnrolment::query()
        ->where('student_id', $student->id)
        ->firstOrFail();

    $enrolment->update([
        'student_enrolment_status_id' => $referredId,
    ]);

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semesterId,
        ],
        [
            'programme_semester_id' => $firstProgrammeSemesterId,
            'student_enrolment_status_id' => $referredId,
        ],
    );

    $student->load(['applications', 'enrolments.studentSemesters.studentEnrolmentStatus']);
    $pathways = app(StudentCoursePathwayProgressService::class)->buildForStudent($student);
    $nd = collect($pathways[0]['stages'])->firstWhere('levelName', 'ND');

    expect($nd['steps'][0]['state'] ?? null)->toBe('blocked')
        ->and($nd['steps'][1]['state'] ?? null)->toBe('locked');
});

it('omits courses that have an application but no enrolment', function (): void {
    $context = createItPathwayContext(ndRequiresNc: true);
    $student = $context['student'];
    $application = $context['ndApplication'];

    $otherCourse = Course::factory()->create();
    $otherDepartmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $application->tenant_id,
        'institution_department_id' => $application->institution_department_id,
        'course_id' => $otherCourse->id,
    ]);

    DepartmentLevelCourse::query()->create([
        'department_course_id' => $otherDepartmentCourse->id,
        'department_level_id' => $application->department_level_id,
    ]);

    $rejected = resolveWorkflowStep(WorkflowStepEnum::REJECTED);

    StudentApplication::query()->create([
        'tenant_id' => $application->tenant_id,
        'student_id' => $student->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $otherDepartmentCourse->id,
        'intake_period_id' => $application->intake_period_id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'application_tracking_number' => 'APP-REJ-'.Str::upper(Str::random(6)),
        'program_status_id' => ClassListTypeEnum::FAILED->value,
        'workflow_step_id' => $rejected->id,
    ]);

    $student->load(['applications', 'enrolments']);
    $pathways = app(StudentCoursePathwayProgressService::class)->buildForStudent($student);

    expect($pathways)->toHaveCount(1)
        ->and($pathways[0]['departmentCourseId'])->toBe($context['departmentCourse']->id);
});

it('includes pathways in the programmes profile payload', function (): void {
    $context = createItPathwayContext(ndRequiresNc: true);
    $payload = app(StudentProgrammeDataService::class)->buildProfilePayload($context['student']);

    expect($payload)->toHaveKeys(['programmes', 'pathways'])
        ->and($payload['pathways'])->toHaveCount(1);
});
