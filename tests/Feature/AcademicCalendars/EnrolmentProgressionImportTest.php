<?php

declare(strict_types=1);

use App\Actions\Students\CompleteLevelEnrolmentAction;
use App\Enums\AcademicCalendars\EnrolmentProgressionImportAction;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\Level;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Models\Users\User;
use App\Services\AcademicCalendars\EnrolmentProgressionImportService;
use App\Services\Students\ReturningStudentContextService;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    foreach (['Semester 1', 'Semester 2'] as $name) {
        Semester::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Award', 'Absent', 'Deferred', 'Disqualified', 'Proceed', 'Referred'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }

    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

function createPhaseEnrolmentForNextLevel(string $studentNumber, string $semesterSlug = 'semester-2', string $statusName = 'Active'): StudentEnrolment
{
    $studentApplication = createVerifiedStudentApplication($studentNumber);
    $studentApplication->departmentLevel->level->update([
        'calendar_type' => 'semester',
        'name' => 'NC',
        'position' => 5,
        'show_on_current_application_period' => 1,
    ]);

    Level::query()->firstOrCreate(
        ['name' => 'ND'],
        [
            'description' => 'National Diploma',
            'position' => 6,
            'calendar_type' => 'semester',
            'show_on_current_application_period' => 1,
            'has_application_fee_payment' => true,
        ],
    );

    $ndLevel = Level::query()->where('name', 'ND')->firstOrFail();
    DepartmentLevel::query()->firstOrCreate(
        [
            'institution_department_id' => $studentApplication->institution_department_id,
            'level_id' => $ndLevel->id,
        ],
        ['tenant_id' => $studentApplication->tenant_id],
    );

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $statusId = (int) StudentEnrolmentStatus::query()->where('name', $statusName)->value('id');
    $semesterId = (int) Semester::query()->where('slug', $semesterSlug)->value('id');

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $studentApplication->student_id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $statusId,
    ]);

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semesterId,
        ],
        [
            'student_enrolment_status_id' => $statusId,
        ],
    );

    return $enrolment->fresh([
        'studentEnrolmentStatus',
        'departmentLevel.level',
        'studentApplication.departmentLevel.level',
        'student',
    ]) ?? $enrolment;
}

function createClassSeatingForEnrolment(StudentEnrolment $enrolment): array
{
    $tenantId = $enrolment->studentApplication?->tenant_id
        ?? $enrolment->student?->tenant_id;

    $classConfig = ClassConfig::query()->create([
        'tenant_id' => $tenantId,
        'institution_department_id' => $enrolment->institution_department_id,
        'department_level_id' => $enrolment->department_level_id,
        'department_course_id' => $enrolment->department_course_id,
        'mode_of_study_id' => $enrolment->mode_of_study_id,
        'calendar_year' => '2026',
        'semester_id' => $enrolment->semester_id,
    ]);

    $class = AcademicCalendarClass::query()->create([
        'tenant_id' => $tenantId,
        'class_config_id' => $classConfig->id,
        'name' => 'A',
        'description' => null,
    ]);

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $tenantId,
        'student_enrolment_id' => $enrolment->id,
        'academic_calendar_class_id' => $class->id,
        'is_live' => true,
    ]);

    return compact('classConfig', 'class');
}

function makeStudentNumberSpreadsheet(array $studentNumbers): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Student Number');

    foreach (array_values($studentNumbers) as $index => $number) {
        $sheet->setCellValue('A'.($index + 2), $number);
    }

    $path = storage_path('framework/testing/progression-import-'.Str::random(8).'.xlsx');
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('exposes next-level context after award when a higher department level exists', function (): void {
    $enrolment = createPhaseEnrolmentForNextLevel('NEXT-LVL-1', 'semester-2', 'Active');
    app(CompleteLevelEnrolmentAction::class)->execute($enrolment->fresh());

    $student = $enrolment->student->fresh(['enrolments.studentEnrolmentStatus', 'enrolments.departmentLevel.level']);
    $context = app(ReturningStudentContextService::class)->nextLevelApplicationContext($student);

    expect($context['canApplyToNextLevel'])->toBeTrue()
        ->and($context['nextLevelName'])->toBe('ND')
        ->and($context['nextDepartmentLevelId'])->not->toBeNull()
        ->and($context['institutionDepartmentId'])->toBe((int) $enrolment->institution_department_id);
});

it('does not allow next-level apply while enrolment is still active', function (): void {
    $enrolment = createPhaseEnrolmentForNextLevel('NEXT-LVL-ACTIVE', 'semester-2', 'Active');
    $student = $enrolment->student->fresh(['enrolments.studentEnrolmentStatus', 'enrolments.departmentLevel.level']);

    expect(app(ReturningStudentContextService::class)->canApplyToNextLevel($student))->toBeFalse()
        ->and(app(ReturningStudentContextService::class)->canStartApplication($student))->toBeFalse();
});

it('resolves the next department level by position', function (): void {
    $enrolment = createPhaseEnrolmentForNextLevel('NEXT-LVL-RESOLVE', 'semester-2', 'Award');
    $current = app(StudentEnrolmentProgressionService::class)->departmentLevelForEnrolment($enrolment);
    $next = app(StudentEnrolmentProgressionService::class)->nextDepartmentLevel($current);

    expect($next)->not->toBeNull()
        ->and($next?->level?->name)->toBe('ND');
});

it('imports student numbers and marks eligible last-phase enrolments as award', function (): void {
    Storage::fake('local');

    $enrolment = createPhaseEnrolmentForNextLevel('IMPORT-COMPLETE-1', 'semester-2', 'Active');
    ['classConfig' => $classConfig] = createClassSeatingForEnrolment($enrolment);
    $department = $enrolment->studentApplication?->institutionDepartment
        ?? \App\Models\Institution\InstitutionDepartment::query()->findOrFail($enrolment->institution_department_id);

    $file = makeStudentNumberSpreadsheet([
        (string) $enrolment->student?->student_number,
        'UNKNOWN-999',
    ]);

    $service = app(EnrolmentProgressionImportService::class);
    $preview = $service->preview(
        $file,
        $department,
        $classConfig,
        EnrolmentProgressionImportAction::CompleteLevel,
    );

    expect($preview['summary']['eligible'])->toBe(1)
        ->and($preview['summary']['skipped'])->toBe(1);

    $eligible = collect($preview['rows'])->firstWhere('eligible', true);
    $result = $service->process(
        [[
            'studentEnrolmentId' => $eligible['studentEnrolmentId'],
            'academicCalendarClassId' => $eligible['academicCalendarClassId'],
        ]],
        $department,
        $classConfig,
        EnrolmentProgressionImportAction::CompleteLevel,
    );

    expect($result['processed'])->toBe(1)
        ->and($enrolment->fresh()->studentEnrolmentStatus?->slug)->toBe('award');
});

it('skips mid-phase students on complete-level import', function (): void {
    $enrolment = createPhaseEnrolmentForNextLevel('IMPORT-MID', 'semester-1', 'Active');
    ['classConfig' => $classConfig] = createClassSeatingForEnrolment($enrolment);
    $department = \App\Models\Institution\InstitutionDepartment::query()->findOrFail($enrolment->institution_department_id);

    $file = makeStudentNumberSpreadsheet([(string) $enrolment->student?->student_number]);
    $preview = app(EnrolmentProgressionImportService::class)->preview(
        $file,
        $department,
        $classConfig,
        EnrolmentProgressionImportAction::CompleteLevel,
    );

    expect($preview['summary']['eligible'])->toBe(0)
        ->and($preview['summary']['skipped'])->toBe(1);
});

it('allows downloading the progression import template for complete-level and advance-phase', function (): void {
    $enrolment = createPhaseEnrolmentForNextLevel('IMPORT-TEMPLATE', 'semester-2', 'Active');
    ['classConfig' => $classConfig] = createClassSeatingForEnrolment($enrolment);
    $user = User::factory()->create(['tenant_id' => $enrolment->studentApplication?->tenant_id]);
    Permission::findOrCreate('update:academic-calendar-student-enrolments', 'web');
    $user->givePermissionTo('update:academic-calendar-student-enrolments');

    $params = [
        'institution_department' => $enrolment->institution_department_id,
        'calendar_year' => '2026',
        'class_config_id' => $classConfig->id,
        'department_level_id' => $enrolment->department_level_id,
        'department_course_id' => $enrolment->department_course_id,
        'mode_of_study_id' => $enrolment->mode_of_study_id,
    ];

    $this->actingAs($user)
        ->get(route('academic-calendars.department-classes.progression-import.template', [
            ...$params,
            'action' => 'complete-level',
        ]))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('academic-calendars.department-classes.progression-import.template', [
            ...$params,
            'action' => 'advance-phase',
        ]))
        ->assertOk();
});
