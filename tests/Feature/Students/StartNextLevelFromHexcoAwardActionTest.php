<?php

declare(strict_types=1);

use App\Actions\Students\StartNextLevelFromHexcoAwardAction;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Students\StudentExamResultComment;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Enrolments\ClassList;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Carbon;

require_once __DIR__.'/../../Support/ExamAwardTestHelpers.php';

beforeEach(function (): void {
    seedExamAwardLookups();
    Carbon::setTestNow(Carbon::parse('2026-09-03', config('app.timezone')));
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

function seedNcAwardSitting(array $context, string $session = '2026-06-01'): StudentExamResult
{
    $student = $context['student'];

    return StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C0'.random_int(1000, 9999),
        'department_level_id' => $context['ncDepartmentLevel']->id,
        'institution_department_id' => $context['enrolment']->institution_department_id,
        'department_course_id' => $context['enrolment']->department_course_id,
        'calendar_year' => (int) substr($session, 0, 4),
        'session' => $session,
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);
}

it('aligns an existing FINAL ND enrolment to Year 1 Sem 1 in the half after a June NC award', function (): void {
    $context = createAugustIntakeNdContext();
    $examResult = seedNcAwardSitting($context);

    $phase = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->firstOrFail();
    $phase->update([
        'student_enrolment_status_id' => (int) StudentEnrolmentStatus::query()
            ->where('slug', 'unknown')
            ->value('id'),
    ]);

    $outcome = app(StartNextLevelFromHexcoAwardAction::class)
        ->execute($context['student'], $examResult);

    $phase = $phase->fresh(['semester', 'programmeSemester', 'studentEnrolmentStatus']);
    $firstTaught = $context['nd']->programmeSemesters->sortBy('position')->first();
    $august = AcademicCalendar::query()
        ->where('calendar_year', '2026')
        ->whereDate('opening_date', '2026-08-17')
        ->firstOrFail();

    expect($outcome['status'])->toBe('started')
        ->and($outcome['awarded_level'])->toBe('NC')
        ->and($outcome['next_level'])->toBe('ND')
        ->and($phase->semester->slug)->toBe('semester-2')
        ->and((int) $phase->programme_semester_id)->toBe((int) $firstTaught->id)
        ->and($phase->studentEnrolmentStatus->slug)->toBe('active')
        ->and((int) $context['enrolment']->fresh()->academic_calendar_id)->toBe((int) $august->id)
        ->and(StudentSemester::query()->where('student_enrolment_id', $context['enrolment']->id)->count())->toBe(1);
});

it('does not create an ND enrolment when there is no next-level application', function (): void {
    $context = createAugustIntakeNdContext();
    $student = $context['student'];

    // Drop the ND application/enrolment so only the NC award evidence remains.
    $enrolmentIds = StudentEnrolment::query()->where('student_id', $student->id)->pluck('id');
    StudentSemester::query()->whereIn('student_enrolment_id', $enrolmentIds)->forceDelete();
    StudentEnrolment::query()->where('student_id', $student->id)->forceDelete();
    ClassList::query()
        ->whereIn('student_application_id', StudentApplication::query()->where('student_id', $student->id)->pluck('id'))
        ->forceDelete();
    StudentApplication::query()->where('student_id', $student->id)->forceDelete();

    $examResult = StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C09999',
        'department_level_id' => $context['ncDepartmentLevel']->id,
        'institution_department_id' => $context['ncDepartmentLevel']->institution_department_id,
        'department_course_id' => $context['nd']->department_course_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);

    $outcome = app(StartNextLevelFromHexcoAwardAction::class)->execute($student, $examResult);

    expect($outcome['status'])->toBe('skipped_no_application')
        ->and(StudentEnrolment::query()->where('student_id', $student->id)->count())->toBe(0);
});

it('skips a next-level application that is not FINAL and has no enrolment yet', function (): void {
    $context = createAugustIntakeNdContext();
    $student = $context['student'];

    $enrolmentIds = StudentEnrolment::query()->where('student_id', $student->id)->pluck('id');
    StudentSemester::query()->whereIn('student_enrolment_id', $enrolmentIds)->forceDelete();
    StudentEnrolment::query()->where('student_id', $student->id)->forceDelete();

    $application = StudentApplication::query()->where('student_id', $student->id)->firstOrFail();
    ClassList::query()->updateOrCreate(
        ['student_application_id' => $application->id],
        [
            'tenant_id' => $application->tenant_id,
            'type' => ClassListTypeEnum::PROVISIONAL->value,
            'attributes' => [],
        ],
    );

    $examResult = StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C08888',
        'department_level_id' => $context['ncDepartmentLevel']->id,
        'institution_department_id' => $application->institution_department_id,
        'department_course_id' => $application->department_course_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);

    $outcome = app(StartNextLevelFromHexcoAwardAction::class)->execute($student, $examResult);

    expect($outcome['status'])->toBe('skipped_application_not_final')
        ->and(StudentEnrolment::query()->where('student_id', $student->id)->count())->toBe(0);
});

it('creates an ND Year 1 Sem 1 enrolment from a FINAL application after a June NC award', function (): void {
    $context = createAugustIntakeNdContext();
    $student = $context['student'];

    StudentEnrolment::query()->where('student_id', $student->id)->forceDelete();
    StudentSemester::query()->whereIn(
        'student_enrolment_id',
        StudentEnrolment::withTrashed()->where('student_id', $student->id)->pluck('id'),
    )->forceDelete();

    $application = StudentApplication::query()->where('student_id', $student->id)->firstOrFail();
    ClassList::query()->updateOrCreate(
        ['student_application_id' => $application->id],
        [
            'tenant_id' => $application->tenant_id,
            'type' => ClassListTypeEnum::FINAL->value,
            'attributes' => [],
        ],
    );

    $examResult = StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C07777',
        'department_level_id' => $context['ncDepartmentLevel']->id,
        'institution_department_id' => $application->institution_department_id,
        'department_course_id' => $application->department_course_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);

    $outcome = app(StartNextLevelFromHexcoAwardAction::class)->execute($student, $examResult);

    $enrolment = StudentEnrolment::query()->where('student_id', $student->id)->first();
    $phases = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment?->id)
        ->with(['semester', 'programmeSemester', 'studentEnrolmentStatus'])
        ->get();
    $firstTaught = $context['nd']->programmeSemesters->sortBy('position')->first();

    expect($outcome['status'])->toBe('started')
        ->and($enrolment)->not->toBeNull()
        ->and($phases)->toHaveCount(1)
        ->and($phases->first()->semester->slug)->toBe('semester-2')
        ->and((int) $phases->first()->programme_semester_id)->toBe((int) $firstTaught->id)
        ->and($phases->first()->studentEnrolmentStatus->slug)->toBe('active');
});

it('is idempotent when the same award claim is processed again', function (): void {
    $context = createAugustIntakeNdContext();
    $examResult = seedNcAwardSitting($context);
    $action = app(StartNextLevelFromHexcoAwardAction::class);

    $first = $action->execute($context['student'], $examResult);
    $second = $action->execute($context['student'], $examResult->fresh() ?? $examResult);

    expect($first['status'])->toBe('started')
        ->and($second['status'])->toBe('started')
        ->and(StudentEnrolment::query()->where('student_id', $context['student']->id)->count())->toBe(1)
        ->and(StudentSemester::query()->where('student_enrolment_id', $context['enrolment']->id)->count())->toBe(1);
});

it('starts the next level in semester 1 of the following year after a year-end award', function (): void {
    $context = createAugustIntakeNdContext();
    $student = $context['student'];

    AcademicCalendar::query()->firstOrCreate(
        ['calendar_year' => '2027', 'type' => 'semester', 'opening_date' => '2027-02-01'],
        ['closing_date' => '2027-06-04'],
    );
    AcademicCalendar::query()->firstOrCreate(
        ['calendar_year' => '2027', 'type' => 'semester', 'opening_date' => '2027-08-16'],
        ['closing_date' => '2027-12-03'],
    );

    StudentEnrolment::query()->where('student_id', $student->id)->forceDelete();

    $application = StudentApplication::query()->where('student_id', $student->id)->firstOrFail();
    ClassList::query()->updateOrCreate(
        ['student_application_id' => $application->id],
        [
            'tenant_id' => $application->tenant_id,
            'type' => ClassListTypeEnum::FINAL->value,
            'attributes' => [],
        ],
    );

    $examResult = StudentExamResult::query()->create([
        'tenant_id' => $student->tenant_id,
        'student_id' => $student->id,
        'candidate_number' => '0625001C06666',
        'department_level_id' => $context['ncDepartmentLevel']->id,
        'institution_department_id' => $application->institution_department_id,
        'department_course_id' => $application->department_course_id,
        'calendar_year' => 2026,
        'session' => '2026-11-01',
        'comment' => StudentExamResultComment::Award,
        'raw_course_comment' => 'AWARD',
    ]);

    // Claim processed in November — target half is still the period after the sitting.
    Carbon::setTestNow(Carbon::parse('2026-11-15', config('app.timezone')));

    $outcome = app(StartNextLevelFromHexcoAwardAction::class)->execute($student, $examResult);

    $enrolment = StudentEnrolment::query()
        ->where('student_id', $student->id)
        ->with(['academicCalendar', 'studentSemesters.semester', 'studentSemesters.programmeSemester'])
        ->first();
    $firstTaught = $context['nd']->programmeSemesters->sortBy('position')->first();

    expect($outcome['status'])->toBe('started')
        ->and($enrolment?->academicCalendar?->calendar_year)->toBe('2027')
        ->and((string) $enrolment?->academicCalendar?->opening_date)->toStartWith('2027-02-01')
        ->and($enrolment?->studentSemesters)->toHaveCount(1)
        ->and($enrolment?->studentSemesters->first()?->semester?->slug)->toBe('semester-1')
        ->and((int) $enrolment?->studentSemesters->first()?->programme_semester_id)->toBe((int) $firstTaught->id);
});

it('does not start the next level for a Proceed comment', function (): void {
    $context = createAugustIntakeNdContext();
    $examResult = seedNcAwardSitting($context);
    $examResult->update([
        'comment' => StudentExamResultComment::Proceed,
        'raw_course_comment' => 'PROCEED',
    ]);

    $outcome = app(StartNextLevelFromHexcoAwardAction::class)
        ->execute($context['student'], $examResult->fresh() ?? $examResult);

    expect($outcome['status'])->toBe('skipped_not_award');
});

it('never writes the NC award onto the ND Year 1 Sem 1 phase', function (): void {
    $context = createAugustIntakeNdContext();
    $examResult = seedNcAwardSitting($context);

    app(StartNextLevelFromHexcoAwardAction::class)->execute($context['student'], $examResult);

    $awarded = StudentSemester::query()
        ->where('student_enrolment_id', $context['enrolment']->id)
        ->whereHas('studentEnrolmentStatus', fn ($query) => $query->where('slug', StudentEnrolmentProgressionService::STATUS_AWARD))
        ->count();

    expect($awarded)->toBe(0)
        ->and(
            StudentSemester::query()
                ->where('student_enrolment_id', $context['enrolment']->id)
                ->first()
                ?->studentEnrolmentStatus
                ?->slug,
        )->toBe('active');
});
