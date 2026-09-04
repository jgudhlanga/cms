<?php

declare(strict_types=1);

use App\Enums\Students\StudentExamResultComment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Maintenance\Students\ExamAwardPhaseRepairService;
use Illuminate\Support\Carbon;

require_once __DIR__.'/../../Support/ExamAwardTestHelpers.php';

beforeEach(function (): void {
    seedExamAwardLookups();

    // Mid-way through the year's second semester.
    Carbon::setTestNow(Carbon::parse('2026-09-03', config('app.timezone')));
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

/**
 * @param  'nc'|'nd'  $level  which level the sitting is filed against
 */
function seedSitting(
    array $context,
    StudentExamResultComment $comment,
    string $level = 'nc',
    string $session = '2026-06-01',
): StudentExamResult {
    $student = $context['student'];

    return StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C0'.random_int(1000, 9999),
        'department_level_id' => $level === 'nc'
            ? $context['ncDepartmentLevel']->id
            : $context['enrolment']->department_level_id,
        'institution_department_id' => $context['enrolment']->institution_department_id,
        'department_course_id' => $context['enrolment']->department_course_id,
        'calendar_year' => 2026,
        'session' => $session,
        'comment' => $comment,
        'raw_course_comment' => $comment->value,
    ]);
}

it('repairs a student whose latest sitting awards a level below their current one', function (): void {
    $context = createAugustIntakeNdContext();
    seedSitting($context, StudentExamResultComment::Award);

    // The bug being repaired: the award was filed onto the new level's first phase.
    $phase = StudentSemester::query()->where('student_enrolment_id', $context['enrolment']->id)->firstOrFail();
    $phase->update([
        'student_enrolment_status_id' => (int) StudentEnrolmentStatus::query()->where('slug', 'award')->value('id'),
    ]);

    $result = app(ExamAwardPhaseRepairService::class)->run(dryRun: false);

    expect($result['repaired'])->toHaveCount(1)
        ->and($result['repaired'][0]['student_id'])->toBe((int) $context['student']->id)
        ->and($result['repaired'][0]['to_level_name'])->toBe('NC')
        ->and($result['repaired'][0]['current_level_name'])->toBe('ND')
        ->and($phase->fresh()->studentEnrolmentStatus->slug)->not->toBe('award');
});

it('leaves a student alone when the award belongs to the level they are on', function (): void {
    $context = createAugustIntakeNdContext();
    seedSitting($context, StudentExamResultComment::Award, level: 'nd');

    // The student has reached ND's completion phase, so an ND award is genuine.
    $completion = $context['nd']->programmeSemesters->sortByDesc('position')->first();
    StudentSemester::query()->where('student_enrolment_id', $context['enrolment']->id)->firstOrFail()->update([
        'programme_semester_id' => $completion->id,
    ]);

    expect(app(ExamAwardPhaseRepairService::class)->run(dryRun: false)['repaired'])->toBe([]);
});

it('repairs an award filed against the current level that the student cannot have finished', function (): void {
    $context = createAugustIntakeNdContext();
    seedSitting($context, StudentExamResultComment::Award, level: 'nd');

    // Still on ND Year 1 Sem 1 — an ND award here can only be the prior level's.
    $result = app(ExamAwardPhaseRepairService::class)->run(dryRun: false);

    expect($result['repaired'])->toHaveCount(1)
        ->and($result['repaired'][0]['to_level_name'])->toBe('NC');
});

it('leaves a student alone when the latest sitting is not an award', function (): void {
    $context = createAugustIntakeNdContext();
    seedSitting($context, StudentExamResultComment::Award, session: '2026-06-01');
    seedSitting($context, StudentExamResultComment::Proceed, session: '2026-11-01');

    expect(app(ExamAwardPhaseRepairService::class)->run(dryRun: false)['repaired'])->toBe([]);
});

it('never touches a student with no exam results', function (): void {
    $context = createAugustIntakeNdContext();
    $before = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->get()
        ->map(fn (StudentSemester $phase): array => [
            $phase->id,
            $phase->student_enrolment_status_id,
            $phase->programme_semester_id,
            (string) $phase->updated_at,
        ])
        ->all();

    Carbon::setTestNow(Carbon::parse('2026-09-04', config('app.timezone')));
    $result = app(ExamAwardPhaseRepairService::class)->run(dryRun: false);

    $after = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->get()
        ->map(fn (StudentSemester $phase): array => [
            $phase->id,
            $phase->student_enrolment_status_id,
            $phase->programme_semester_id,
            (string) $phase->updated_at,
        ])
        ->all();

    expect($result['repaired'])->toBe([])
        ->and($after)->toBe($before);
});

it('writes nothing on a dry run', function (): void {
    $context = createAugustIntakeNdContext();
    $examResult = seedSitting($context, StudentExamResultComment::Award);

    $phase = StudentSemester::query()->where('student_enrolment_id', $context['enrolment']->id)->firstOrFail();
    $awardId = (int) StudentEnrolmentStatus::query()->where('slug', 'award')->value('id');
    $phase->update(['student_enrolment_status_id' => $awardId]);

    $result = app(ExamAwardPhaseRepairService::class)->run(dryRun: true);

    expect($result['repaired'])->toHaveCount(1)
        ->and($result['run_id'])->toBeNull()
        ->and((int) $phase->fresh()->student_enrolment_status_id)->toBe($awardId)
        ->and((int) $examResult->fresh()->department_level_id)->toBe((int) $context['ncDepartmentLevel']->id);
});
