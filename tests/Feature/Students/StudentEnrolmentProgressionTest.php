<?php

declare(strict_types=1);

use App\Actions\Students\AdvanceToNextSemesterAction;
use App\Actions\Students\CompleteLevelEnrolmentAction;
use App\Actions\Students\UpdateStudentEnrolmentStatusAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

function createPhaseEnrolment(string $studentNumber, string $semesterSlug = 'semester-1', string $statusName = 'Active'): StudentEnrolment
{
    $studentApplication = createVerifiedStudentApplication($studentNumber);
    $studentApplication->departmentLevel->level->update(['calendar_type' => 'semester']);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $statusId = (int) StudentEnrolmentStatus::query()->where('name', $statusName)->value('id');
    $semesterId = (int) Semester::query()->where('slug', $semesterSlug)->value('id');

    return StudentEnrolment::query()->create([
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
}

it('advances an active first-phase enrolment to semester two on the same enrolment', function (): void {
    $enrolment = createPhaseEnrolment('ADV-S1');
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $proceedId = (int) StudentEnrolmentStatus::query()->where('slug', 'proceed')->value('id');

    $next = app(AdvanceToNextSemesterAction::class)->execute($enrolment);

    expect($next->id)->toBe($enrolment->id)
        ->and((int) $next->semester_id)->toBe($semesterTwoId)
        ->and((int) $next->student_enrolment_status_id)->toBe($activeId)
        ->and((int) $next->student_application_id)->toBe((int) $enrolment->student_application_id)
        ->and(StudentEnrolment::query()->where('student_application_id', $enrolment->student_application_id)->count())->toBe(1);

    $semesterOne = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', Semester::query()->where('slug', 'semester-1')->value('id'))
        ->first();

    expect((int) $semesterOne?->student_enrolment_status_id)->toBe($proceedId);
});

it('refuses to advance a last-phase enrolment', function (): void {
    $enrolment = createPhaseEnrolment('ADV-LAST', 'semester-2');

    app(AdvanceToNextSemesterAction::class)->execute($enrolment);
})->throws(StudentEnrolmentProgressionException::class);

it('refuses to advance a referred enrolment', function (): void {
    $enrolment = createPhaseEnrolment('ADV-REFERRED', 'semester-1', 'Referred');

    app(AdvanceToNextSemesterAction::class)->execute($enrolment);
})->throws(StudentEnrolmentProgressionException::class);

it('completes the level on the last student_semester only', function (): void {
    $enrolment = createPhaseEnrolment('COMPLETE-LEVEL', 'semester-2');
    $activeId = (int) $enrolment->student_enrolment_status_id;
    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semesterOneId,
        ],
        ['student_enrolment_status_id' => $activeId],
    );

    $semesterTwo = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $enrolment->semester_id)
        ->firstOrFail();

    app(CompleteLevelEnrolmentAction::class)->execute($semesterTwo);

    $awardId = (int) StudentEnrolmentStatus::query()->where('slug', 'award')->value('id');

    expect((int) StudentSemester::query()->where('student_enrolment_id', $enrolment->id)->where('semester_id', $semesterOneId)->value('student_enrolment_status_id'))->toBe($activeId)
        ->and((int) $semesterTwo->fresh()?->student_enrolment_status_id)->toBe($awardId);
});

it('refuses to complete the level on the first phase', function (): void {
    $enrolment = createPhaseEnrolment('COMPLETE-EARLY', 'semester-1');
    $semesterOne = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $enrolment->semester_id)
        ->firstOrFail();

    app(CompleteLevelEnrolmentAction::class)->execute($semesterOne);
})->throws(StudentEnrolmentProgressionException::class);

it('sets referred status on the current student_semester', function (): void {
    $enrolment = createPhaseEnrolment('STATUS-REFERRED');
    $semester = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $enrolment->semester_id)
        ->firstOrFail();

    app(UpdateStudentEnrolmentStatusAction::class)->execute(
        $semester,
        StudentEnrolmentProgressionService::STATUS_REFERRED,
    );

    $referredId = (int) StudentEnrolmentStatus::query()->where('slug', 'referred')->value('id');

    expect((int) $semester->fresh()?->student_enrolment_status_id)->toBe($referredId)
        ->and((int) $enrolment->fresh()?->student_enrolment_status_id)->toBe($referredId);
});

it('dry-runs advance-phase without creating a next enrolment', function (): void {
    $enrolment = createPhaseEnrolment('CMD-ADV');

    $this->artisan('enrolments:advance-phase', [
        '--department' => $enrolment->institution_department_id,
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(StudentEnrolment::query()->where('student_application_id', $enrolment->student_application_id)->count())->toBe(1);
});
