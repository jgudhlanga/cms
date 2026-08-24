<?php

declare(strict_types=1);

require_once __DIR__.'/../../Support/AcademicCalendarClassTestHelpers.php';

use App\Actions\Students\AdvanceToNextSemesterAction;
use App\Actions\Students\CompleteLevelEnrolmentAction;
use App\Actions\Students\UpdateStudentEnrolmentStatusAction;
use App\Actions\Students\UpsertYearStudentEnrolmentAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
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
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Models\Tenants\Tenant;
use App\Services\Students\BackfillStudentSemestersService;
use App\Services\Students\RollbackStudentSemestersService;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['semester-1', 'semester-2'] as $slug) {
        Semester::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::headline(str_replace('-', ' ', $slug)), 'description' => null],
        );
    }

    foreach (['Active', 'Award', 'Proceed'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }

    Carbon::setTestNow(Carbon::parse('2026-09-15', config('app.timezone')));
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

function createSemesterPivotContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'pivot-test',
        'description' => 'Pivot test department',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create(['name' => 'Level 1', 'calendar_type' => 'semester']);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);
    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time Pivot']);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Semester 1 2026',
        'calendar_year' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);
    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);
    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $classConfig = ClassConfig::query()->create([
        'calendar_year' => '2026',
        'institution_department_id' => $institutionDepartment->id,
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'semester_id' => $semesterTwoId,
        'students_per_class' => 30,
    ]);

    return compact(
        'tenant',
        'institutionDepartment',
        'departmentCourse',
        'departmentLevel',
        'modeOfStudy',
        'intakePeriod',
        'calendar',
        'semesterOneId',
        'semesterTwoId',
        'activeId',
        'classConfig',
    );
}

function createFinalApplicationForPivot(array $context, string $trackingNumber): StudentApplication
{
    $studentApplication = createFinalStudentApplication($context, $trackingNumber);

    app(BackfillStudentSemestersService::class)->run();

    return $studentApplication->fresh() ?? $studentApplication;
}


it('backfills semester one and two for current year when today is in semester two window', function (): void {
    $context = createSemesterPivotContext();
    $application = createFinalApplicationForPivot($context, 'backfill-s2@test.com');

    $enrolment = StudentEnrolment::query()
        ->where('student_application_id', $application->id)
        ->first();

    expect($enrolment)->not->toBeNull();

    $semesters = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->orderBy('semester_id')
        ->get();

    expect($semesters)->toHaveCount(2)
        ->and($semesters->pluck('semester_id')->map(fn ($id) => (int) $id)->all())
        ->toContain($context['semesterOneId'], $context['semesterTwoId']);
});

it('is idempotent when backfill runs twice', function (): void {
    $context = createSemesterPivotContext();
    createFinalApplicationForPivot($context, 'backfill-idempotent@test.com');

    $countAfterFirst = StudentSemester::query()->count();
    app(BackfillStudentSemestersService::class)->run();
    $countAfterSecond = StudentSemester::query()->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
});

it('rollback restores original enrolment columns after backfill', function (): void {
    $context = createSemesterPivotContext();
    $application = createFinalApplicationForPivot($context, 'rollback@test.com');

    $enrolment = StudentEnrolment::query()->where('student_application_id', $application->id)->firstOrFail();
    $originalSemesterId = (int) $enrolment->semester_id;
    $originalStatusId = (int) $enrolment->student_enrolment_status_id;

    app(RollbackStudentSemestersService::class)->run();

    $enrolment->refresh();

    expect(StudentSemester::query()->count())->toBe(0)
        ->and((int) $enrolment->semester_id)->toBe($originalSemesterId)
        ->and((int) $enrolment->student_enrolment_status_id)->toBe($originalStatusId);
});

it('finalise in semester two creates enrolment with both semester phases', function (): void {
    $context = createSemesterPivotContext();
    $studentApplication = createVerifiedStudentApplication('FINALISE-S2');

    $studentApplication->departmentLevel->level->update(['calendar_type' => 'semester']);
    $studentApplication->update([
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'intake_period_id' => $context['intakePeriod']->id,
    ]);

    ClassList::query()
        ->where('student_application_id', $studentApplication->id)
        ->update([
            'type' => ClassListTypeEnum::FINAL->value,
            'attributes' => [],
        ]);

    app(UpsertYearStudentEnrolmentAction::class)->execute($studentApplication);

    $enrolment = StudentEnrolment::query()->where('student_application_id', $studentApplication->id)->first();

    expect($enrolment)->not->toBeNull()
        ->and(StudentSemester::query()->where('student_enrolment_id', $enrolment->id)->count())->toBe(2);
});

it('advances within the same year without creating a second enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $context = createSemesterPivotContext();
    $context['classConfig']->update(['semester_id' => $context['semesterOneId']]);
    $application = createFinalApplicationForPivot($context, 'advance-same-year@test.com');
    $enrolment = StudentEnrolment::query()->where('student_application_id', $application->id)->firstOrFail();

    expect(StudentSemester::query()->where('student_enrolment_id', $enrolment->id)->count())->toBe(1);

    app(AdvanceToNextSemesterAction::class)->execute($enrolment->fresh());

    expect(StudentEnrolment::query()->where('student_application_id', $application->id)->count())->toBe(1)
        ->and(StudentSemester::query()->where('student_enrolment_id', $enrolment->id)->count())->toBe(2);
});

it('pins syllabus on semester two without clearing semester one syllabi', function (): void {
    $context = createSemesterPivotContext();
    $application = createFinalApplicationForPivot($context, 'syllabus-isolation@test.com');
    $enrolment = StudentEnrolment::query()->where('student_application_id', $application->id)->firstOrFail();

    $semesterOne = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['semesterOneId'])
        ->firstOrFail();
    $semesterTwo = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['semesterTwoId'])
        ->firstOrFail();

    app(StudentEnrolmentProgressionService::class)->pinSyllabusIds($enrolment, [11, 12], $semesterOne);
    app(StudentEnrolmentProgressionService::class)->pinSyllabusIds($enrolment, [21], $semesterTwo);

    expect($semesterOne->fresh()?->course_syllabus_ids)->toBe([11, 12])
        ->and($semesterTwo->fresh()?->course_syllabus_ids)->toBe([21]);
});

it('completes the level on the last student_semester only', function (): void {
    $context = createSemesterPivotContext();
    $application = createFinalApplicationForPivot($context, 'complete-level@test.com');
    $enrolment = StudentEnrolment::query()->where('student_application_id', $application->id)->firstOrFail();

    $semesterTwo = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['semesterTwoId'])
        ->firstOrFail();

    app(UpdateStudentEnrolmentStatusAction::class)->execute(
        $semesterTwo,
        StudentEnrolmentProgressionService::STATUS_ACTIVE,
    );

    app(CompleteLevelEnrolmentAction::class)->execute($semesterTwo->fresh());

    $awardId = (int) StudentEnrolmentStatus::query()->where('slug', 'award')->value('id');

    expect((int) $semesterTwo->fresh()?->student_enrolment_status_id)->toBe($awardId);
});
