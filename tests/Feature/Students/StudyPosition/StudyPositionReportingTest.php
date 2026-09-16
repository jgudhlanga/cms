<?php

declare(strict_types=1);

use App\Actions\Students\ConfirmStudyPositionAction;
use App\Enums\Students\StudyPositionAnswerEnum;
use App\Enums\Students\StudyPositionSourceEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Students\StudentEnrolment;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * One student in each state, plus one from last year who is out of scope.
 *
 * @return array{context: array<string, mixed>, students: array<string, int>}
 */
function makeStudyPositionReportingFixture(): array
{
    $context = makeStudyPositionContext();
    $offering = makeMultiYearOffering($context);
    [$y1s1, $y1s2] = $offering['phases'];

    $bothSemesters = [
        ['slug' => 'semester-1', 'phase' => $y1s1],
        ['slug' => 'semester-2', 'phase' => $y1s2],
    ];

    $unconfirmed = makeEnrolmentWithPhases($context, $offering, $bothSemesters);
    $confirmed = makeEnrolmentWithPhases($context, $offering, $bothSemesters);
    $followUp = makeEnrolmentWithPhases($context, $offering, $bothSemesters);
    $review = makeEnrolmentWithPhases($context, $offering, $bothSemesters);

    $answer = static fn (StudentEnrolment $enrolment, StudyPositionAnswerEnum $type, ?ProgrammeSemester $phase) => app(ConfirmStudyPositionAction::class)
        ->execute($enrolment, $type, $phase, StudyPositionSourceEnum::STUDENT);

    $answer($confirmed, StudyPositionAnswerEnum::PHASE, $y1s2);
    $answer($followUp, StudyPositionAnswerEnum::NOT_SURE, null);
    $answer($review, StudyPositionAnswerEnum::PHASE, $y1s1);

    $lastYear = AcademicCalendar::query()->create([
        'calendar_year' => '2025',
        'type' => 'semester',
        'opening_date' => '2025-02-01',
        'closing_date' => '2025-06-30',
    ]);
    $pastOnly = makeEnrolmentWithPhases($context, $offering, [['slug' => 'semester-1', 'phase' => $y1s1]]);
    StudentEnrolment::withoutEvents(fn () => $pastOnly->update(['academic_calendar_id' => $lastYear->id]));

    $context['user']->givePermissionTo(['viewAny:students', 'export:students']);
    Sanctum::actingAs($context['user']);

    return [
        'context' => $context,
        'students' => [
            'unconfirmed' => (int) $unconfirmed->student_id,
            'confirmed' => (int) $confirmed->student_id,
            'follow_up' => (int) $followUp->student_id,
            'needs_review' => (int) $review->student_id,
            'past_only' => (int) $pastOnly->student_id,
        ],
    ];
}

it('filters the student list by study position', function (): void {
    ['students' => $students] = makeStudyPositionReportingFixture();

    $idsFor = fn (string $state): array => collect($this->getJson(route('v1.students.index', ['study_position' => $state]))
        ->assertOk()
        ->json('data'))
        ->pluck('id')
        ->map(fn ($id): int => (int) $id)
        ->sort()
        ->values()
        ->all();

    expect($idsFor('unconfirmed'))->toBe([$students['unconfirmed']])
        ->and($idsFor('confirmed'))->toBe([$students['confirmed']])
        ->and($idsFor('follow_up'))->toBe([$students['follow_up']])
        ->and($idsFor('needs_review'))->toBe([$students['needs_review']])
        ->and($idsFor('attention'))->toBe(collect([
            $students['unconfirmed'],
            $students['follow_up'],
            $students['needs_review'],
        ])->sort()->values()->all());
});

it('counts students in each study position state', function (): void {
    makeStudyPositionReportingFixture();

    $response = $this->getJson(route('v1.students.stats'))->assertOk();
    $buckets = collect($response->json('global.byStudyPosition'))->pluck('count', 'id')->all();

    expect($buckets)->toBe([
        'unconfirmed' => 1,
        'follow_up' => 1,
        'needs_review' => 1,
        'confirmed' => 1,
    ])->and($response->json('global.studyPositionPeriodLabel'))->toBe('2026 · Semester 2');

    $this->getJson(route('v1.students.stats', ['study_position' => 'confirmed']))
        ->assertOk()
        ->assertJsonPath('filtered.total', 1);
});

it('exports the study position columns', function (): void {
    ['context' => $context] = makeStudyPositionReportingFixture();

    $response = $this->get(route('students.export', [
        'department' => [$context['institutionDepartment']->id],
        'study_position' => 'needs_review',
    ]))->assertSuccessful();

    $rows = IOFactory::load($response->getFile()->getPathname())->getActiveSheet()->toArray();
    $headers = $rows[0];
    $row = array_combine($headers, $rows[1]);

    expect($rows)->toHaveCount(2)
        ->and($headers)->toContain('Study Position Status', 'Confirmed Phase', 'Phase on Record', 'Review Note')
        ->and($row['Study Period'])->toBe('2026 · Semester 2')
        ->and($row['Study Position Status'])->toBe('Registry review')
        ->and($row['Confirmed Via'])->toBe('Student')
        ->and($row['Review Note'])->not->toBeEmpty();
});

it('rejects an unknown study position filter on export', function (): void {
    ['context' => $context] = makeStudyPositionReportingFixture();

    $this->from(route('students.index'))
        ->get(route('students.export', [
            'department' => [$context['institutionDepartment']->id],
            'study_position' => 'bogus',
        ]))
        ->assertSessionHasErrors('study_position');
});

it('reports study position progress on the department reconciliation counts', function (): void {
    ['context' => $context] = makeStudyPositionReportingFixture();
    $this->actingAs($context['user']);

    $this->getJson(route('department-data-reconciliation.counts', [
        'department' => $context['institutionDepartment']->id,
        'calendar_year' => 2026,
    ]))
        ->assertOk()
        ->assertJsonPath('studyPosition.periodLabel', '2026 · Semester 2')
        ->assertJsonPath('studyPosition.inScope', 4)
        ->assertJsonPath('studyPosition.confirmed', 1)
        ->assertJsonPath('studyPosition.unconfirmed', 1)
        ->assertJsonPath('studyPosition.followUp', 1)
        ->assertJsonPath('studyPosition.needsReview', 1);

    $this->getJson(route('department-data-reconciliation.counts', [
        'department' => $context['institutionDepartment']->id,
        'calendar_year' => 2025,
    ]))
        ->assertOk()
        ->assertJsonPath('studyPosition', null);
});
