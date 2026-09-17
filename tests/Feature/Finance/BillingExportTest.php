<?php

declare(strict_types=1);

use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Enums\Students\StudyPositionSyncStatusEnum;
use App\Http\Requests\Finance\ExportForBillingRequest;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Finance\PastelLinkedStudent;
use App\Models\Finance\StudentBillingRecord;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Rbac\Permission;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentStudyPositionConfirmation;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Finance\BillingExportService;
use App\Support\AcademicCalendars\AcademicCalendarPeriodResolver;
use App\Support\Institution\ProgrammeSemesterNameFormatter;

function billingExportUser(?int $tenantId = null): User
{
    $user = User::factory()->create([
        'tenant_id' => $tenantId ?? Tenant::query()->firstOrFail()->id,
    ]);
    Permission::findOrCreate('export-for-billing:finances', 'web');
    $user->givePermissionTo('export-for-billing:finances');

    return $user;
}

/**
 * @return array{
 *     context: array<string, mixed>,
 *     offering: array<string, mixed>,
 *     firstPhase: ProgrammeSemester,
 *     secondPhase: ProgrammeSemester,
 *     period: AcademicCalendar
 * }
 */
function createBillingExportWorld(): array
{
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$firstPhase, $secondPhase] = $offering['phases'];

    return [
        'context' => $context,
        'offering' => $offering,
        'firstPhase' => $firstPhase,
        'secondPhase' => $secondPhase,
        'period' => $context['semesterTwo'],
    ];
}

/**
 * @param  array{context: array<string, mixed>, offering: array<string, mixed>, firstPhase: ProgrammeSemester, secondPhase: ProgrammeSemester, period: AcademicCalendar}  $world
 * @return array{enrolment: StudentEnrolment, phase: ProgrammeSemester, period: AcademicCalendar, confirmation: StudentStudyPositionConfirmation}
 */
function addBillingExportStudent(
    array $world,
    StudyPositionSyncStatusEnum $syncStatus = StudyPositionSyncStatusEnum::UNCHANGED,
    StudyPositionAnswerEnum $answer = StudyPositionAnswerEnum::PHASE,
    ?AcademicCalendar $period = null,
    ?ProgrammeSemester $phase = null,
): array {
    $enrolment = makeEnrolmentWithPhases($world['context'], $world['offering'], [
        ['slug' => 'semester-1', 'phase' => $world['firstPhase']],
        ['slug' => 'semester-2', 'phase' => $world['secondPhase']],
    ]);

    $resolvedPhase = $phase ?? $world['secondPhase'];
    $resolvedPeriod = $period ?? $world['period'];

    $confirmation = StudentStudyPositionConfirmation::query()->create([
        'tenant_id' => $world['context']['tenantId'],
        'student_id' => $enrolment->student_id,
        'student_enrolment_id' => $enrolment->id,
        'academic_calendar_id' => $resolvedPeriod->id,
        'semester_id' => $world['context']['slotTwo']->id,
        'programme_semester_id' => $answer === StudyPositionAnswerEnum::PHASE ? $resolvedPhase->id : null,
        'answer' => $answer,
        'source' => StudyPositionSourceEnum::ADMIN,
        'sync_status' => $syncStatus,
        'confirmed_at' => now(),
    ]);

    $enrolment->load([
        'student',
        'institutionDepartment.department',
        'departmentLevel.level',
        'departmentCourse.course',
        'modeOfStudy',
        'studentApplication.modeOfStudy',
    ]);

    return [
        'enrolment' => $enrolment,
        'phase' => $resolvedPhase,
        'period' => $resolvedPeriod,
        'confirmation' => $confirmation,
    ];
}

/**
 * @return array{
 *     context: array<string, mixed>,
 *     offering: array<string, mixed>,
 *     firstPhase: ProgrammeSemester,
 *     secondPhase: ProgrammeSemester,
 *     enrolment: StudentEnrolment,
 *     phase: ProgrammeSemester,
 *     period: AcademicCalendar,
 *     confirmation: StudentStudyPositionConfirmation
 * }
 */
function createBillingExportFixture(): array
{
    $world = createBillingExportWorld();
    $student = addBillingExportStudent($world);

    return $world + $student;
}

test('guests are redirected when visiting billing export pages', function (): void {
    $this->get(route('finance.billing-export.index'))->assertRedirect('/login');
    $this->post(route('finance.billing-export.download', ['academic_calendar_ids' => [1]]))->assertRedirect('/login');
});

test('authenticated users without billing export permissions cannot access billing export', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('finance.billing-export.index'))->assertForbidden();
    $this->actingAs($user)->post(route('finance.billing-export.download', ['academic_calendar_ids' => [1]]))->assertForbidden();
});

test('authenticated users with billing export permission can visit billing export page', function (): void {
    $user = billingExportUser();

    $this->actingAs($user)->get(route('finance.billing-export.index'))->assertSuccessful();
});

test('billing export download returns csv with expected headers and mapped row', function (): void {
    $fixture = createBillingExportFixture();
    $enrolment = $fixture['enrolment'];
    $user = billingExportUser($fixture['context']['tenantId']);

    $response = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ]);

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $csv = array_map('str_getcsv', file($response->getFile()->getPathname()));
    expect($csv[0])->toBe(BillingExportService::HEADERS);

    $levelName = $enrolment->departmentLevel->level->name;
    $row = $csv[1];
    expect($row[0])->toBe($enrolment->student->student_number);
    expect($row[1])->toBe($enrolment->student->id_number);
    expect($row[2])->toBe($enrolment->institutionDepartment->department->name);
    expect($row[3])->toBe($levelName);
    expect($row[4])->toBe($enrolment->departmentCourse->course->name);
    expect($row[5])->toBe(ProgrammeSemesterNameFormatter::qualifiedName($levelName, $fixture['phase']->name));
    expect($row[6])->toBe(
        $fixture['period']->calendar_year.' · '.AcademicCalendarPeriodResolver::displayPeriodLabel($fixture['period']),
    );
    expect($row[7])->toBe($enrolment->modeOfStudy->name);
    expect($row[8])->toBe('Non-Resident');
});

test('billing export excludes unconfirmed follow-up and needs-review students', function (): void {
    $world = createBillingExportWorld();
    $confirmed = addBillingExportStudent($world);
    $followUp = addBillingExportStudent(
        $world,
        StudyPositionSyncStatusEnum::NOT_APPLICABLE,
        StudyPositionAnswerEnum::NOT_SURE,
    );
    $needsReview = addBillingExportStudent(
        $world,
        StudyPositionSyncStatusEnum::NEEDS_REVIEW,
    );

    $unconfirmedEnrolment = makeEnrolmentWithPhases(
        $world['context'],
        $world['offering'],
        [['slug' => 'semester-2', 'phase' => $world['secondPhase']]],
    );

    $user = billingExportUser($world['context']['tenantId']);

    $response = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$world['period']->id],
        'student_number_starts_with' => '',
    ]);
    $response->assertSuccessful();

    $csv = array_map('str_getcsv', file($response->getFile()->getPathname()));
    $studentNumbers = array_column(array_slice($csv, 1), 0);

    expect($studentNumbers)->toContain($confirmed['enrolment']->student->student_number)
        ->and($studentNumbers)->not->toContain($followUp['enrolment']->student->student_number)
        ->and($studentNumbers)->not->toContain($needsReview['enrolment']->student->student_number)
        ->and($studentNumbers)->not->toContain($unconfirmedEnrolment->student->student_number);
});

test('billing export download creates exported records and does not mark billed', function (): void {
    $fixture = createBillingExportFixture();
    $user = billingExportUser($fixture['context']['tenantId']);

    $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $record = StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->sole();

    expect($record->status->value)->toBe('exported')
        ->and($record->billed_at)->toBeNull()
        ->and($record->programme_semester_id)->toBe($fixture['phase']->id);

    $this->actingAs($user)
        ->get(route('finance.billing-export.index', [
            'academic_calendar_ids' => [$fixture['period']->id],
            'student_number_starts_with' => '',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('finance/BillingExport')
            ->where('exportCount', 1)
            ->where('billingStats.readyToBill', 1)
            ->where('billingStats.total', 0)
            ->has('billingRecords.data', 1));
});

test('mark as billed drops the ready count and mark failed returns the student', function (): void {
    $fixture = createBillingExportFixture();
    $user = billingExportUser($fixture['context']['tenantId']);

    $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $record = StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->sole();

    $this->actingAs($user)
        ->from(route('finance.billing-export.index'))
        ->post(route('finance.billing-export.mark-billed'), ['ids' => [$record->id]])
        ->assertRedirect();

    expect($record->fresh()->status->value)->toBe('billed');

    $this->actingAs($user)
        ->get(route('finance.billing-export.index', [
            'academic_calendar_ids' => [$fixture['period']->id],
            'student_number_starts_with' => '',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('exportCount', 0)
            ->where('billingStats.readyToBill', 0)
            ->where('billingStats.total', 1)
            ->where('billingStats.billedToday', 1));

    $this->actingAs($user)
        ->from(route('finance.billing-export.index'))
        ->delete(route('finance.billing-export.records.destroy', $record))
        ->assertRedirect();

    expect(StudentBillingRecord::query()->whereKey($record->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('finance.billing-export.index', [
            'academic_calendar_ids' => [$fixture['period']->id],
            'student_number_starts_with' => '',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('exportCount', 1)
            ->where('billingStats.readyToBill', 1)
            ->where('billingStats.total', 0));
});

test('mark failed removes exported records so they can be exported again', function (): void {
    $fixture = createBillingExportFixture();
    $user = billingExportUser($fixture['context']['tenantId']);

    $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $record = StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->sole();

    $this->actingAs($user)
        ->from(route('finance.billing-export.index'))
        ->post(route('finance.billing-export.mark-failed'), ['ids' => [$record->id]])
        ->assertRedirect();

    expect(StudentBillingRecord::query()->whereKey($record->id)->exists())->toBeFalse();

    $secondResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ]);
    $secondResponse->assertSuccessful();
    $csv = array_map('str_getcsv', file($secondResponse->getFile()->getPathname()));
    expect($csv)->toHaveCount(2);
    expect(StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->count())->toBe(1);
});

test('a student whose confirmed phase changes after being billed becomes billable again', function (): void {
    $fixture = createBillingExportFixture();
    $user = billingExportUser($fixture['context']['tenantId']);
    $firstPhase = $fixture['firstPhase'];

    $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $record = StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->sole();

    $this->actingAs($user)
        ->post(route('finance.billing-export.mark-billed'), ['ids' => [$record->id]])
        ->assertRedirect();

    $fixture['confirmation']->update(['programme_semester_id' => $firstPhase->id]);

    $response = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ]);
    $response->assertSuccessful();
    $csv = array_map('str_getcsv', file($response->getFile()->getPathname()));
    expect($csv)->toHaveCount(2);
    expect(StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->count())->toBe(2);
});

test('re-export of the same phase does not duplicate billing records', function (): void {
    $fixture = createBillingExportFixture();
    $user = billingExportUser($fixture['context']['tenantId']);

    $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $secondResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ]);
    $secondResponse->assertSuccessful();
    $csv = array_map('str_getcsv', file($secondResponse->getFile()->getPathname()));
    expect($csv)->toHaveCount(2);
    expect(StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->count())->toBe(1);
});

test('billing export filters by student number prefix when provided', function (): void {
    $world = createBillingExportWorld();
    $matched = addBillingExportStudent($world);
    $other = addBillingExportStudent($world);

    $matched['enrolment']->student->update(['student_number' => '26EE06017338HP']);
    $other['enrolment']->student->update(['student_number' => '25EE06017338HP']);

    $user = billingExportUser($world['context']['tenantId']);

    $prefixResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$world['period']->id],
        'student_number_starts_with' => '26',
    ]);
    $prefixResponse->assertSuccessful();
    $prefixCsv = array_map('str_getcsv', file($prefixResponse->getFile()->getPathname()));
    expect($prefixCsv)->toHaveCount(2);
    expect($prefixCsv[1][0])->toBe('26EE06017338HP');

    $allResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$world['period']->id],
        'student_number_starts_with' => '',
    ]);
    $allResponse->assertSuccessful();
    $allCsv = array_map('str_getcsv', file($allResponse->getFile()->getPathname()));
    expect($allCsv)->toHaveCount(3);
});

test('billing export filters by billing period', function (): void {
    $world = createBillingExportWorld();
    $semesterTwo = addBillingExportStudent($world);
    $semesterOne = addBillingExportStudent(
        $world,
        StudyPositionSyncStatusEnum::UNCHANGED,
        StudyPositionAnswerEnum::PHASE,
        $world['context']['semesterOne'],
    );

    $user = billingExportUser($world['context']['tenantId']);

    $matchedResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$world['period']->id],
        'student_number_starts_with' => '',
    ]);
    $matchedResponse->assertSuccessful();
    $matchedCsv = array_map('str_getcsv', file($matchedResponse->getFile()->getPathname()));
    $numbers = array_column(array_slice($matchedCsv, 1), 0);

    expect($numbers)->toContain($semesterTwo['enrolment']->student->student_number)
        ->and($numbers)->not->toContain($semesterOne['enrolment']->student->student_number);
});

test('billing export index defaults student number prefix filter to 26', function (): void {
    $user = billingExportUser();

    $this->actingAs($user)
        ->get(route('finance.billing-export.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('finance/BillingExport')
            ->where('filters.student_number_starts_with', ExportForBillingRequest::DEFAULT_STUDENT_NUMBER_STARTS_WITH));
});

test('billing export download requires a billing period', function (): void {
    $user = billingExportUser();

    $this->actingAs($user)
        ->from(route('finance.billing-export.index'))
        ->post(route('finance.billing-export.download'))
        ->assertRedirect(route('finance.billing-export.index'))
        ->assertSessionHasErrors(['academic_calendar_ids']);
});

test('billing export can filter students already linked in pastel', function (): void {
    $world = createBillingExportWorld();
    $linked = addBillingExportStudent($world);
    $unlinked = addBillingExportStudent($world);

    PastelLinkedStudent::query()->create([
        'tenant_id' => $world['context']['tenantId'],
        'student_id' => $linked['enrolment']->student_id,
        'student_number' => $linked['enrolment']->student->student_number,
        'linked_by' => null,
        'linked_at' => now(),
    ]);

    $user = billingExportUser($world['context']['tenantId']);

    $linkedResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$world['period']->id],
        'pastel_linked' => 'linked',
        'student_number_starts_with' => '',
    ]);
    $linkedResponse->assertSuccessful();
    $linkedCsv = array_map('str_getcsv', file($linkedResponse->getFile()->getPathname()));
    expect($linkedCsv)->toHaveCount(2);
    expect($linkedCsv[1][0])->toBe($linked['enrolment']->student->student_number);

    $unlinkedResponse = $this->actingAs($user)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$world['period']->id],
        'pastel_linked' => 'unlinked',
        'student_number_starts_with' => '',
    ]);
    $unlinkedResponse->assertSuccessful();
    $unlinkedCsv = array_map('str_getcsv', file($unlinkedResponse->getFile()->getPathname()));
    expect($unlinkedCsv)->toHaveCount(2);
    expect($unlinkedCsv[1][0])->toBe($unlinked['enrolment']->student->student_number);
});

test('users without billing export permissions cannot mark records billed', function (): void {
    $fixture = createBillingExportFixture();
    $financeUser = billingExportUser($fixture['context']['tenantId']);

    $this->actingAs($financeUser)->post(route('finance.billing-export.download'), [
        'academic_calendar_ids' => [$fixture['period']->id],
        'student_number_starts_with' => '',
    ])->assertSuccessful();

    $record = StudentBillingRecord::query()->where('student_id', $fixture['enrolment']->student_id)->sole();
    $user = User::factory()->create(['tenant_id' => $fixture['context']['tenantId']]);

    $this->actingAs($user)
        ->post(route('finance.billing-export.mark-billed'), ['ids' => [$record->id]])
        ->assertForbidden();
});

test('bulk unbill requires at least one id', function (): void {
    $user = billingExportUser();

    $this->actingAs($user)
        ->from(route('finance.billing-export.index'))
        ->delete(route('finance.billing-export.records.bulk-destroy'), [
            'ids' => [],
        ])
        ->assertRedirect(route('finance.billing-export.index'))
        ->assertSessionHasErrors(['ids']);
});
