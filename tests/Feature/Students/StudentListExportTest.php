<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/../Api/V1/Students/StudentIndexFilterTest.php';

it('requires export permission to download student list', function (): void {
    $program = createVerifiedStudentApplication('STU-EXP-'.strtoupper(Str::random(4)));
    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $user->givePermissionTo('viewAny:students');

    createStudentEnrolmentForProgram($program);

    $this->actingAs($user)
        ->get(route('students.export', ['department' => [$program->institution_department_id]]))
        ->assertForbidden();
});

it('validates department is required for student list export', function (): void {
    $program = createVerifiedStudentApplication('STU-EXP-'.strtoupper(Str::random(4)));
    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $this->actingAs($user)
        ->from(route('students.index'))
        ->get(route('students.export'))
        ->assertRedirect(route('students.index'))
        ->assertSessionHasErrors(['department']);
});

it('exports students filtered by department', function (): void {
    $matchedProgram = createVerifiedStudentApplication('STU-MAT-'.strtoupper(Str::random(4)));
    $otherProgram = createVerifiedStudentApplication('STU-OTH-'.strtoupper(Str::random(4)));

    createStudentEnrolmentForProgram($matchedProgram);
    createStudentEnrolmentForProgram($otherProgram);

    $user = User::factory()->create(['tenant_id' => $matchedProgram->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $response = $this->actingAs($user)->get(route('students.export', [
        'department' => [$matchedProgram->institution_department_id],
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname());
    $rows = $spreadsheet->getActiveSheet()->toArray();
    $flat = collect($rows)->flatten()->filter()->implode(' ');

    expect($flat)->toContain('STU-MAT-');
    expect($flat)->not->toContain('STU-OTH-');
});

it('matches export row count with index and stats for department, level, and mode filters', function (): void {
    $matchedProgram = createVerifiedStudentApplication('STU-EXP-M-'.strtoupper(Str::random(4)));
    $otherModeProgram = createVerifiedStudentApplication('STU-EXP-O-'.strtoupper(Str::random(4)));

    $ojet = \App\Models\Institution\ModeOfStudy::query()->firstOrCreate(['name' => 'Ojet']);
    $fullTime = \App\Models\Institution\ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);

    $matchedProgram->update(['mode_of_study_id' => $ojet->id]);
    $otherModeProgram->update([
        'institution_department_id' => $matchedProgram->institution_department_id,
        'department_level_id' => $matchedProgram->department_level_id,
        'department_course_id' => $matchedProgram->department_course_id,
        'mode_of_study_id' => $fullTime->id,
    ]);

    createStudentEnrolmentForProgram($matchedProgram);
    createStudentEnrolmentForProgram($otherModeProgram);

    $user = User::factory()->create(['tenant_id' => $matchedProgram->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $levelId = (int) $matchedProgram->departmentLevel->level_id;
    $filters = [
        'department' => [$matchedProgram->institution_department_id],
        'level' => [$levelId],
        'mode_of_study' => [$ojet->id],
    ];

    Sanctum::actingAs($user);
    $indexResponse = $this->getJson(route('v1.students.index', $filters));
    $statsResponse = $this->getJson(route('v1.students.stats', $filters));
    $indexResponse->assertOk();
    $statsResponse->assertOk();

    $exportResponse = $this->actingAs($user)->get(route('students.export', $filters));
    $exportResponse->assertSuccessful();

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($exportResponse->getFile()->getPathname());
    $rows = $spreadsheet->getActiveSheet()->toArray();
    $dataRows = collect($rows)->slice(1)->filter(fn ($row) => collect($row)->filter()->isNotEmpty())->values();

    expect($indexResponse->json('meta.total'))
        ->toBe($statsResponse->json('filtered.total'))
        ->toBe($dataRows->count())
        ->toBe(1);

    $flat = $dataRows->flatten()->filter()->implode(' ');
    expect($flat)->toContain('STU-EXP-M-')
        ->and($flat)->not->toContain('STU-EXP-O-');
});

it('applies student_type filter on student list export', function (): void {
    $directProgram = createVerifiedStudentApplication('STU-EXP-D-'.strtoupper(Str::random(4)));
    $apprenticeProgram = createVerifiedStudentApplication('STU-EXP-A-'.strtoupper(Str::random(4)));

    $apprenticeProgram->update([
        'institution_department_id' => $directProgram->institution_department_id,
        'department_level_id' => $directProgram->department_level_id,
        'department_course_id' => $directProgram->department_course_id,
        'mode_of_study_id' => $directProgram->mode_of_study_id,
    ]);

    createStudentEnrolmentForProgram($directProgram);
    createStudentEnrolmentForProgram($apprenticeProgram);

    \App\Models\Students\StudentApprentice::query()->create([
        'tenant_id' => $apprenticeProgram->tenant_id,
        'student_id' => $apprenticeProgram->student_id,
        'calendar_year' => 2026,
        'employer' => 'Export Employer',
        'apprentice_number' => 'APP-EXP-001',
    ]);

    $user = User::factory()->create(['tenant_id' => $directProgram->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $response = $this->actingAs($user)->get(route('students.export', [
        'department' => [$directProgram->institution_department_id],
        'student_type' => 'apprentice',
    ]));

    $response->assertSuccessful();

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname());
    $rows = $spreadsheet->getActiveSheet()->toArray();
    $flat = collect($rows)->flatten()->filter()->implode(' ');

    expect($flat)->toContain('STU-EXP-A-')
        ->and($flat)->not->toContain('STU-EXP-D-');
});

it('does not duplicate multi-enrolment students in export versus index total', function (): void {
    $program = createVerifiedStudentApplication('STU-DUP-'.strtoupper(Str::random(4)));
    createStudentEnrolmentForProgram($program);
    createStudentEnrolmentForProgram($program);

    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $filters = [
        'department' => [$program->institution_department_id],
    ];

    Sanctum::actingAs($user);
    $indexResponse = $this->getJson(route('v1.students.index', $filters));
    $indexResponse->assertOk();

    $exportResponse = $this->actingAs($user)->get(route('students.export', $filters));
    $exportResponse->assertSuccessful();

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($exportResponse->getFile()->getPathname());
    $rows = $spreadsheet->getActiveSheet()->toArray();
    $dataRows = collect($rows)->slice(1)->filter(fn ($row) => collect($row)->filter()->isNotEmpty())->values();
    $studentNumbers = $dataRows->map(fn ($row) => $row[2] ?? null)->filter();

    expect($indexResponse->json('meta.total'))
        ->toBe(1)
        ->toBe($dataRows->count())
        ->and($studentNumbers->unique()->count())->toBe($studentNumbers->count());
});
