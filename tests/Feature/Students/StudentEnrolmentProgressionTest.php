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

    foreach (['Active', 'Completed', 'Repeat/Re-write', 'Deferred/Postponed'] as $name) {
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

it('advances an active first-phase enrolment to semester two while remaining active', function (): void {
    $enrolment = createPhaseEnrolment('ADV-S1');
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $next = app(AdvanceToNextSemesterAction::class)->execute($enrolment);

    expect($next->id)->not->toBe($enrolment->id)
        ->and((int) $next->semester_id)->toBe($semesterTwoId)
        ->and((int) $next->student_enrolment_status_id)->toBe($activeId)
        ->and((int) $next->student_application_id)->toBe((int) $enrolment->student_application_id)
        ->and($enrolment->fresh()?->student_enrolment_status_id)->toBe($activeId);
});

it('refuses to advance a last-phase enrolment', function (): void {
    $enrolment = createPhaseEnrolment('ADV-LAST', 'semester-2');

    app(AdvanceToNextSemesterAction::class)->execute($enrolment);
})->throws(StudentEnrolmentProgressionException::class);

it('refuses to advance a repeat enrolment', function (): void {
    $enrolment = createPhaseEnrolment('ADV-REPEAT', 'semester-1', 'Repeat/Re-write');

    app(AdvanceToNextSemesterAction::class)->execute($enrolment);
})->throws(StudentEnrolmentProgressionException::class);

it('completes the level on the last phase for every enrolment of the application', function (): void {
    $first = createPhaseEnrolment('COMPLETE-LEVEL', 'semester-1');
    $second = StudentEnrolment::query()->create([
        'student_id' => $first->student_id,
        'student_application_id' => $first->student_application_id,
        'institution_department_id' => $first->institution_department_id,
        'department_level_id' => $first->department_level_id,
        'department_course_id' => $first->department_course_id,
        'semester_id' => (int) Semester::query()->where('slug', 'semester-2')->value('id'),
        'academic_calendar_id' => $first->academic_calendar_id,
        'mode_of_study_id' => $first->mode_of_study_id,
        'student_enrolment_status_id' => $first->student_enrolment_status_id,
    ]);

    app(CompleteLevelEnrolmentAction::class)->execute($second->fresh());

    $completedId = (int) StudentEnrolmentStatus::query()->where('slug', 'completed')->value('id');

    expect((int) $first->fresh()?->student_enrolment_status_id)->toBe($completedId)
        ->and((int) $second->fresh()?->student_enrolment_status_id)->toBe($completedId);
});

it('refuses to complete the level on the first phase', function (): void {
    $enrolment = createPhaseEnrolment('COMPLETE-EARLY', 'semester-1');

    app(CompleteLevelEnrolmentAction::class)->execute($enrolment);
})->throws(StudentEnrolmentProgressionException::class);

it('sets repeat status on every enrolment for the application', function (): void {
    $enrolment = createPhaseEnrolment('STATUS-REPEAT');

    app(UpdateStudentEnrolmentStatusAction::class)->execute(
        $enrolment,
        StudentEnrolmentProgressionService::STATUS_REPEAT,
    );

    $repeatId = (int) StudentEnrolmentStatus::query()->where('slug', 'repeatre-write')->value('id');

    expect((int) $enrolment->fresh()?->student_enrolment_status_id)->toBe($repeatId);
});

it('dry-runs advance-phase without creating a next enrolment', function (): void {
    $enrolment = createPhaseEnrolment('CMD-ADV');

    $this->artisan('enrolments:advance-phase', [
        '--department' => $enrolment->institution_department_id,
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(StudentEnrolment::query()->where('student_application_id', $enrolment->student_application_id)->count())->toBe(1);
});
