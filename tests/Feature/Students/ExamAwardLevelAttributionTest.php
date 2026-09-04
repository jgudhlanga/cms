<?php

declare(strict_types=1);

use App\Enums\Students\StudentExamResultComment;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentCoursePathwayProgressService;
use App\Services\Students\StudentProgrammeDataService;
use Illuminate\Support\Carbon;

require_once __DIR__.'/../../Support/ExamAwardTestHelpers.php';

/**
 * Reproduces student 23746: an NC HEXCO statement awarded in the June 2026 sitting, for a student
 * who has since started ND in the second half of 2026. The award closes NC — it must not tick off
 * ND Year 1 Sem 1, and ND must not gain a phase for the year's first calendar semester.
 */
beforeEach(function (): void {
    seedExamAwardLookups();

    // Mid-way through the year's second semester.
    Carbon::setTestNow(Carbon::parse('2026-09-03', config('app.timezone')));
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('gives an August intake a single phase pinned to the level first programme semester', function (): void {
    $context = createAugustIntakeNdContext();

    $phases = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->with('semester')
        ->get();

    $firstTaught = $context['nd']->programmeSemesters->sortBy('position')->first();

    expect($phases)->toHaveCount(1)
        ->and($phases->first()->semester->slug)->toBe('semester-2')
        ->and((int) $phases->first()->programme_semester_id)->toBe((int) $firstTaught->id);
});

it('does not write a prior-level award onto the new level first phase', function (): void {
    $context = createAugustIntakeNdContext();
    $student = $context['student'];

    // The NC statement, correctly attributed to NC, sat in the June sitting.
    StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C00457',
        'department_level_id' => $context['ncDepartmentLevel']->id,
        'institution_department_id' => $context['enrolment']->institution_department_id,
        'department_course_id' => $context['enrolment']->department_course_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);

    app(StudentProgrammeDataService::class)->buildProfilePayload($student->fresh() ?? $student);

    $awarded = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->whereHas('studentEnrolmentStatus', fn ($query) => $query->where('slug', 'award'))
        ->count();

    expect($awarded)->toBe(0);
});

it('refuses an award that lands on a phase which does not complete the level', function (): void {
    $context = createAugustIntakeNdContext();
    $student = $context['student'];

    // Mis-attributed the old way: stamped with the student's current (ND) level.
    StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C00457',
        'department_level_id' => $context['enrolment']->department_level_id,
        'institution_department_id' => $context['enrolment']->institution_department_id,
        'department_course_id' => $context['enrolment']->department_course_id,
        'calendar_year' => 2026,
        'session' => '2026-09-01',
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);

    app(StudentProgrammeDataService::class)->buildProfilePayload($student->fresh() ?? $student);

    $awarded = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->whereHas('studentEnrolmentStatus', fn ($query) => $query->where('slug', 'award'))
        ->count();

    expect($awarded)->toBe(0);
});

it('shows the August intake on ND Year 1 Sem 1 with NC implied complete', function (): void {
    $context = createAugustIntakeNdContext();

    $pathways = app(StudentCoursePathwayProgressService::class)
        ->buildForStudent($context['student']->fresh() ?? $context['student']);

    $pathway = $pathways[0];
    $nd = collect($pathway['stages'])->firstWhere('levelName', 'ND');
    $ndSteps = collect($nd['steps']);

    expect($pathway['stepsTotal'])->toBe(6)
        ->and($pathway['stepsCompleted'])->toBe(2)
        ->and($pathway['yearsTotal'])->toBe(3.0)
        ->and($pathway['yearsCompleted'])->toBe(1.0)
        ->and(collect($pathway['stages'])->firstWhere('levelName', 'NC')['impliedComplete'])->toBeTrue()
        ->and($ndSteps->get(0)['state'])->toBe('current')
        ->and($ndSteps->get(1)['state'])->toBe('locked');
});

it('gives a mid-year intake the modules of the phase they are actually on', function (): void {
    $context = createAugustIntakeNdContext();
    $nd = $context['nd'];
    $syllabus = seedTaughtSyllabusForOffering($nd, $context['enrolment']);

    $payload = app(StudentProgrammeDataService::class)
        ->buildProfilePayload($context['student']->fresh() ?? $context['student']);

    $semesters = collect($payload['programmes'])->firstWhere('isActive', true)['semesters'];
    $current = collect($semesters)->firstWhere('isCurrent', true);
    $future = collect($semesters)->firstWhere('isCurrent', false);

    $taught = $nd->programmeSemesters->sortBy('position')->values();

    // The student joined in the calendar's second half, so their live phase is Year 1 Sem 1 and
    // it must carry Year 1 Sem 1 modules, not the calendar-second-semester ones. Labels are
    // level-qualified because every level restarts its numbering at Year 1.
    expect($current['label'])->toBe('ND '.$taught->get(0)->name)
        ->and(collect($current['module'])->pluck('code')->all())->toBe(['Y1S1-A'])
        ->and($future['label'])->toBe('ND '.$taught->get(1)->name)
        ->and(collect($future['module'])->pluck('code')->all())->toBe(['Y1S2-A'])
        ->and($syllabus->id)->toBeGreaterThan(0);
});

it('qualifies every phase name with its level, since each level restarts at Year 1', function (): void {
    $context = createAugustIntakeNdContext();

    $pathway = app(StudentCoursePathwayProgressService::class)
        ->buildForStudent($context['student']->fresh() ?? $context['student'])[0];

    $byLevel = collect($pathway['stages'])->keyBy('levelName');

    // "Year 1 Sem 2" exists under both NC and ND — the short form has to tell them apart.
    expect(collect($byLevel['NC']['steps'])->pluck('shortName')->all())
        ->toBe(['NC Y1 S1', 'NC Y1 S2'])
        ->and(collect($byLevel['ND']['steps'])->pluck('shortName')->all())
        ->toBe(['ND Y1 S1', 'ND Y1 S2', 'ND Y2 Att 1', 'ND Y2 Att 2'])
        ->and(collect($byLevel['ND']['steps'])->pluck('levelName')->unique()->all())->toBe(['ND'])
        ->and($byLevel['ND']['steps'][0]['name'])->toBe('Year 1 Sem 1');
});
