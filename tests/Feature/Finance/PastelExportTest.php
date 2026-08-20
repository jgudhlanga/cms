<?php

use App\Enums\Shared\WorkflowStepEnum;
use App\Http\Requests\Finance\ExportPastelRequest;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Finance\PastelLinkedStudent;
use App\Models\Institution\IntakePeriod;
use App\Models\Rbac\Permission;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Finance\PastelExportService;
use Illuminate\Support\Str;

function createPastelExportEnrolment(
    StudentApplication $program,
    ?StudentEnrolmentStatus $status = null,
    ?IntakePeriod $intakePeriod = null,
): StudentEnrolment {
    $suffix = Str::lower(Str::random(6));

    if ($intakePeriod !== null) {
        $program->update(['intake_period_id' => $intakePeriod->id]);
    }

    $semester = Semester::query()->create([
        'slug' => 'pastel-export-'.$suffix,
        'name' => 'Semester '.$suffix,
        'description' => null,
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $activeStatus = $status ?? StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    return StudentEnrolment::query()->create([
        'student_id' => $program->student_id,
        'student_application_id' => $program->id,
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'semester_id' => $semester->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $program->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatus->id,
    ]);
}

function financeUser(?int $tenantId = null): User
{
    $user = User::factory()->create([
        'tenant_id' => $tenantId ?? Tenant::query()->firstOrFail()->id,
    ]);
    Permission::findOrCreate('export-to-pastel:finances', 'web');
    $user->givePermissionTo('export-to-pastel:finances');

    return $user;
}

test('guests are redirected when visiting pastel export pages', function (): void {
    $this->get(route('finance.pastel-export.index'))->assertRedirect('/login');
    $this->post(route('finance.pastel-export.download', ['intake_period_id' => 1]))->assertRedirect('/login');
});

test('authenticated users without finance permissions cannot access pastel export', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('finance.pastel-export.index'))->assertForbidden();
    $this->actingAs($user)->post(route('finance.pastel-export.download', ['intake_period_id' => 1]))->assertForbidden();
});

test('authenticated users with finance permissions can visit pastel export page', function (): void {
    $user = financeUser();

    $this->actingAs($user)->get(route('finance.pastel-export.index'))->assertSuccessful();
});

test('pastel export download returns csv with expected headers and mapped row', function (): void {
    $program = createVerifiedStudentApplication('PST-'.strtoupper(Str::random(4)));

    createPastelExportEnrolment($program);

    $program->load([
        'student.user',
        'institutionDepartment.department',
        'departmentCourse.course',
        'departmentLevel.level',
        'modeOfStudy',
        'intakePeriod',
    ]);

    $user = financeUser($program->tenant_id);

    $response = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'workflow_step_ids' => [$program->workflow_step_id],
        'student_number_starts_with' => '',
    ]);

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = array_map('str_getcsv', file($response->getFile()->getPathname()));
    expect($csv[0])->toBe(PastelExportService::HEADERS);

    $row = $csv[1];
    expect($row[0])->toBe('STUDENT TEST');
    expect($row[1])->toBe($program->student->student_number);
    expect($row[2])->toBe($program->institutionDepartment->department->name);
    expect($row[3])->toBe($program->departmentCourse->course->name);
    expect($row[4])->toBe($program->departmentLevel->level->name);
    expect($row[6])->toBe($program->modeOfStudy->name);
    expect($row[7])->toBe('No');
    expect($row[8])->toBe('Direct');
    expect($row[14])->toBe('2278');
    expect($row[15])->toBe('2288');
    expect($row[16])->toBe('USD');
});

test('pastel export download marks student as linked once', function (): void {
    $program = createVerifiedStudentApplication('PST-LNK-'.strtoupper(Str::random(4)));
    createPastelExportEnrolment($program);
    createPastelExportEnrolment($program);

    $user = financeUser($program->tenant_id);

    $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    expect(PastelLinkedStudent::query()->where('student_id', $program->student_id)->count())->toBe(1);

    $linked = PastelLinkedStudent::query()->where('student_id', $program->student_id)->first();
    expect($linked)->not->toBeNull();
    expect($linked->student_number)->toBe($program->student->student_number);
    expect($linked->linked_by)->toBe($user->id);
    expect($linked->intake_period_id)->toBe($program->intake_period_id);
});

test('pastel export excludes already linked students and does not duplicate audit rows', function (): void {
    $program = createVerifiedStudentApplication('PST-EXC-'.strtoupper(Str::random(4)));
    createPastelExportEnrolment($program);

    $user = financeUser($program->tenant_id);

    $firstResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ]);
    $firstResponse->assertSuccessful();
    $firstCsv = array_map('str_getcsv', file($firstResponse->getFile()->getPathname()));
    expect($firstCsv)->toHaveCount(2);

    $secondResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ]);
    $secondResponse->assertSuccessful();
    $secondCsv = array_map('str_getcsv', file($secondResponse->getFile()->getPathname()));
    expect($secondCsv)->toHaveCount(1);
    expect(PastelLinkedStudent::query()->where('student_id', $program->student_id)->count())->toBe(1);
});

test('pastel export filters by intake period', function (): void {
    $program = createVerifiedStudentApplication('PST-INT-'.strtoupper(Str::random(4)));

    createPastelExportEnrolment($program);

    $tenant = Tenant::query()->firstOrFail();
    $otherIntake = IntakePeriod::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Other Intake '.Str::upper(Str::random(4)),
        'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
        'end_date' => now()->addMonth()->endOfMonth()->toDateString(),
    ]);

    $user = financeUser($program->tenant_id);

    $matchedResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ]);
    $matchedResponse->assertSuccessful();
    $matchedCsv = array_map('str_getcsv', file($matchedResponse->getFile()->getPathname()));
    expect($matchedCsv)->toHaveCount(2);

    $otherResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $otherIntake->id,
        'student_number_starts_with' => '',
    ]);
    $otherResponse->assertSuccessful();
    $otherCsv = array_map('str_getcsv', file($otherResponse->getFile()->getPathname()));
    expect($otherCsv)->toHaveCount(1);
});

test('pastel export filters by workflow step when provided', function (): void {
    $acceptedProgram = createVerifiedStudentApplication('PST-ACC-'.strtoupper(Str::random(4)));
    $enrolledProgram = createVerifiedStudentApplication('PST-ENR-'.strtoupper(Str::random(4)));

    $enrolledProgram->update([
        'intake_period_id' => $acceptedProgram->intake_period_id,
        'workflow_step_id' => resolveWorkflowStep(WorkflowStepEnum::ENROLLED)->id,
    ]);

    createPastelExportEnrolment($acceptedProgram);
    createPastelExportEnrolment($enrolledProgram);

    $user = financeUser($acceptedProgram->tenant_id);

    $acceptedOnlyResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $acceptedProgram->intake_period_id,
        'workflow_step_ids' => [$acceptedProgram->workflow_step_id],
        'student_number_starts_with' => '',
    ]);
    $acceptedOnlyResponse->assertSuccessful();
    $acceptedOnlyCsv = array_map('str_getcsv', file($acceptedOnlyResponse->getFile()->getPathname()));
    expect($acceptedOnlyCsv)->toHaveCount(2);

    $allStepsResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $acceptedProgram->intake_period_id,
        'student_number_starts_with' => '',
    ]);
    $allStepsResponse->assertSuccessful();
    $allStepsCsv = array_map('str_getcsv', file($allStepsResponse->getFile()->getPathname()));
    expect($allStepsCsv)->toHaveCount(2);
});

test('pastel export index returns export count and linked stats for selected filters', function (): void {
    $program = createVerifiedStudentApplication('PST-CNT-'.strtoupper(Str::random(4)));

    createPastelExportEnrolment($program);

    $user = financeUser($program->tenant_id);

    $this->actingAs($user)
        ->get(route('finance.pastel-export.index', [
            'intake_period_id' => $program->intake_period_id,
            'workflow_step_ids' => [$program->workflow_step_id],
            'student_number_starts_with' => '',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('finance/PastelExport')
            ->where('exportCount', 1)
            ->where('linkedStats.total', 0)
            ->where('linkedStats.linkedToday', 0)
            ->where('linkedStats.readyToExport', 1)
            ->has('linkedStudents.data'));
});

test('pastel export index export count drops after student is linked', function (): void {
    $program = createVerifiedStudentApplication('PST-DRP-'.strtoupper(Str::random(4)));
    createPastelExportEnrolment($program);
    $user = financeUser($program->tenant_id);

    $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $this->actingAs($user)
        ->get(route('finance.pastel-export.index', [
            'intake_period_id' => $program->intake_period_id,
            'student_number_starts_with' => '',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('finance/PastelExport')
            ->where('exportCount', 0)
            ->where('linkedStats.total', 1)
            ->where('linkedStats.linkedToday', 1)
            ->where('linkedStats.readyToExport', 0)
            ->has('linkedStudents.data', 1));
});

test('pastel export index defaults student number prefix filter to 26', function (): void {
    $user = financeUser();

    $this->actingAs($user)
        ->get(route('finance.pastel-export.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('finance/PastelExport')
            ->where('filters.student_number_starts_with', ExportPastelRequest::DEFAULT_STUDENT_NUMBER_STARTS_WITH));
});

test('pastel export index derives student number prefix from selected intake calendar year', function (): void {
    $program = createVerifiedStudentApplication('26EE06017338HP');
    IntakePeriod::query()->whereKey($program->intake_period_id)->update(['calendar_year' => '2026']);
    $user = financeUser($program->tenant_id);

    $this->actingAs($user)
        ->get(route('finance.pastel-export.index', [
            'intake_period_id' => $program->intake_period_id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('finance/PastelExport')
            ->where('filters.student_number_starts_with', '26'));
});

test('pastel export filters by student number prefix when provided', function (): void {
    $matchedProgram = createVerifiedStudentApplication('26EE06017338HP');
    $otherProgram = createVerifiedStudentApplication('25EE06017338HP');

    $otherProgram->update(['intake_period_id' => $matchedProgram->intake_period_id]);

    createPastelExportEnrolment($matchedProgram);
    createPastelExportEnrolment($otherProgram);

    $user = financeUser($matchedProgram->tenant_id);

    $prefixResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $matchedProgram->intake_period_id,
        'student_number_starts_with' => '26',
    ]);
    $prefixResponse->assertSuccessful();
    $prefixCsv = array_map('str_getcsv', file($prefixResponse->getFile()->getPathname()));
    expect($prefixCsv)->toHaveCount(2);
    expect($prefixCsv[1][1])->toBe('26EE06017338HP');

    $allResponse = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $matchedProgram->intake_period_id,
        'student_number_starts_with' => '',
    ]);
    $allResponse->assertSuccessful();
    $allCsv = array_map('str_getcsv', file($allResponse->getFile()->getPathname()));
    expect($allCsv)->toHaveCount(2);
    expect($allCsv[1][1])->toBe('25EE06017338HP');
});

test('pastel export download requires intake period', function (): void {
    $user = financeUser();

    $this->actingAs($user)
        ->from(route('finance.pastel-export.index'))
        ->post(route('finance.pastel-export.download'))
        ->assertRedirect(route('finance.pastel-export.index'))
        ->assertSessionHasErrors(['intake_period_id']);
});

test('unlinking a pastel linked student allows export again', function (): void {
    $program = createVerifiedStudentApplication('PST-UNL-'.strtoupper(Str::random(4)));
    createPastelExportEnrolment($program);
    $user = financeUser($program->tenant_id);

    $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $linked = PastelLinkedStudent::query()->where('student_id', $program->student_id)->firstOrFail();

    $this->actingAs($user)
        ->from(route('finance.pastel-export.index'))
        ->delete(route('finance.pastel-export.linked-students.destroy', $linked))
        ->assertRedirect();

    expect(PastelLinkedStudent::query()->where('student_id', $program->student_id)->exists())->toBeFalse();

    $response = $this->actingAs($user)->post(route('finance.pastel-export.download'), [
        'intake_period_id' => $program->intake_period_id,
        'student_number_starts_with' => '',
    ]);
    $response->assertSuccessful();
    $csv = array_map('str_getcsv', file($response->getFile()->getPathname()));
    expect($csv)->toHaveCount(2);
});

test('users without finance permissions cannot unlink pastel linked students', function (): void {
    $program = createVerifiedStudentApplication('PST-FORB-'.strtoupper(Str::random(4)));
    $financeUser = financeUser($program->tenant_id);

    $linked = PastelLinkedStudent::query()->create([
        'tenant_id' => $program->tenant_id,
        'student_id' => $program->student_id,
        'student_number' => $program->student->student_number,
        'intake_period_id' => $program->intake_period_id,
        'linked_by' => $financeUser->id,
        'linked_at' => now(),
    ]);

    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);

    $this->actingAs($user)
        ->delete(route('finance.pastel-export.linked-students.destroy', $linked))
        ->assertForbidden();
});

test('bulk unlinking removes selected pastel linked students', function (): void {
    $firstProgram = createVerifiedStudentApplication('PST-BLK1-'.strtoupper(Str::random(4)));
    $secondProgram = createVerifiedStudentApplication('PST-BLK2-'.strtoupper(Str::random(4)));
    $thirdProgram = createVerifiedStudentApplication('PST-BLK3-'.strtoupper(Str::random(4)));

    $user = financeUser($firstProgram->tenant_id);

    $first = PastelLinkedStudent::query()->create([
        'tenant_id' => $firstProgram->tenant_id,
        'student_id' => $firstProgram->student_id,
        'student_number' => $firstProgram->student->student_number,
        'intake_period_id' => $firstProgram->intake_period_id,
        'linked_by' => $user->id,
        'linked_at' => now(),
    ]);
    $second = PastelLinkedStudent::query()->create([
        'tenant_id' => $secondProgram->tenant_id,
        'student_id' => $secondProgram->student_id,
        'student_number' => $secondProgram->student->student_number,
        'intake_period_id' => $secondProgram->intake_period_id,
        'linked_by' => $user->id,
        'linked_at' => now(),
    ]);
    $third = PastelLinkedStudent::query()->create([
        'tenant_id' => $thirdProgram->tenant_id,
        'student_id' => $thirdProgram->student_id,
        'student_number' => $thirdProgram->student->student_number,
        'intake_period_id' => $thirdProgram->intake_period_id,
        'linked_by' => $user->id,
        'linked_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('finance.pastel-export.index'))
        ->delete(route('finance.pastel-export.linked-students.bulk-destroy'), [
            'ids' => [$first->id, $second->id],
        ])
        ->assertRedirect();

    expect(PastelLinkedStudent::query()->whereKey($first->id)->exists())->toBeFalse();
    expect(PastelLinkedStudent::query()->whereKey($second->id)->exists())->toBeFalse();
    expect(PastelLinkedStudent::query()->whereKey($third->id)->exists())->toBeTrue();
});

test('users without finance permissions cannot bulk unlink pastel linked students', function (): void {
    $program = createVerifiedStudentApplication('PST-BFB-'.strtoupper(Str::random(4)));
    $financeUser = financeUser($program->tenant_id);

    $linked = PastelLinkedStudent::query()->create([
        'tenant_id' => $program->tenant_id,
        'student_id' => $program->student_id,
        'student_number' => $program->student->student_number,
        'intake_period_id' => $program->intake_period_id,
        'linked_by' => $financeUser->id,
        'linked_at' => now(),
    ]);

    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);

    $this->actingAs($user)
        ->delete(route('finance.pastel-export.linked-students.bulk-destroy'), [
            'ids' => [$linked->id],
        ])
        ->assertForbidden();

    expect(PastelLinkedStudent::query()->whereKey($linked->id)->exists())->toBeTrue();
});

test('bulk unlink requires at least one id', function (): void {
    $user = financeUser();

    $this->actingAs($user)
        ->from(route('finance.pastel-export.index'))
        ->delete(route('finance.pastel-export.linked-students.bulk-destroy'), [
            'ids' => [],
        ])
        ->assertRedirect(route('finance.pastel-export.index'))
        ->assertSessionHasErrors(['ids']);
});
