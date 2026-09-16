<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Actions\Students\AdvanceToNextSemesterAction;
use App\Exceptions\Students\StudentEnrolmentProgressionException;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\ProgrammeSemester;
use App\Models\Institution\ProgrammeStage;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgrammeStage;
use App\Models\Students\StudentSemester;
use App\Services\Students\ProgrammeStageCompletionService;
use App\Services\Students\StudentEnrolmentProgressionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

/**
 * @return array{
 *     enrolment: StudentEnrolment,
 *     dlc: DepartmentLevelCourse,
 *     stage1: ProgrammeStage,
 *     stage2: ProgrammeStage,
 *     semesters: Collection<int, ProgrammeSemester>
 * }
 */
function createMultiYearNcEnrolment(string $studentNumber): array
{
    $application = createVerifiedStudentApplication($studentNumber);
    $application->departmentLevel->level->update([
        'name' => 'NC',
        'calendar_type' => 'semester',
    ]);

    $dlc = DepartmentLevelCourse::query()->firstOrCreate(
        [
            'department_course_id' => $application->department_course_id,
            'department_level_id' => $application->department_level_id,
        ],
        [
            'duration_years' => 2,
            'taught_semester_count' => 4,
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ],
    );

    $dlc->update([
        'duration_years' => 2,
        'taught_semester_count' => 4,
        'includes_industrial_attachment' => false,
        'attachment_semester_count' => 0,
    ]);

    $semesters = app(SyncProgrammeSemestersForOfferingAction::class)->execute($dlc->fresh());
    $stage1 = ProgrammeStage::query()
        ->where('department_level_course_id', $dlc->id)
        ->where('stage_number', 1)
        ->firstOrFail();
    $stage2 = ProgrammeStage::query()
        ->where('department_level_course_id', $dlc->id)
        ->where('stage_number', 2)
        ->firstOrFail();

    $application->update(['programme_stage_id' => $stage1->id]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $nc1Sem1 = programmeSemesterAt($semesters, 1, 1);

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $application->student_id,
        'student_application_id' => $application->id,
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'programme_stage_id' => $stage1->id,
        'semester_id' => $semesterOneId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'student_enrolment_status_id' => $activeId,
    ]);

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semesterOneId,
        ],
        [
            'programme_semester_id' => $nc1Sem1?->id,
            'student_enrolment_status_id' => $activeId,
        ],
    );

    return [
        'enrolment' => $enrolment->fresh(['studentApplication', 'programmeStage', 'studentSemesters']),
        'dlc' => $dlc->fresh(['programmeSemesters', 'programmeStages']),
        'stage1' => $stage1,
        'stage2' => $stage2,
        'semesters' => $semesters,
    ];
}

it('advances within NC 1 from Sem 1 to Sem 2 on the same application', function (): void {
    $ctx = createMultiYearNcEnrolment('STAGE-ADV-S1');
    $enrolment = $ctx['enrolment'];
    $nc1Sem2 = programmeSemesterAt($ctx['semesters'], 1, 2);
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');
    $proceedId = (int) StudentEnrolmentStatus::query()->where('slug', 'proceed')->value('id');

    $next = app(AdvanceToNextSemesterAction::class)->execute($enrolment);

    expect($next->id)->toBe($enrolment->id)
        ->and((int) $next->semester_id)->toBe($semesterTwoId)
        ->and((int) $next->student_application_id)->toBe((int) $enrolment->student_application_id);

    $semesterTwo = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $semesterTwoId)
        ->first();

    expect((int) $semesterTwo?->programme_semester_id)->toBe((int) $nc1Sem2?->id);

    $semesterOne = StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', Semester::query()->where('slug', 'semester-1')->value('id'))
        ->first();

    expect((int) $semesterOne?->student_enrolment_status_id)->toBe($proceedId);
});

it('refuses to advance from NC 1 Sem 2 into NC 2 Sem 1 on the same application', function (): void {
    $ctx = createMultiYearNcEnrolment('STAGE-STOP-S2');
    $enrolment = $ctx['enrolment'];
    $nc1Sem2 = programmeSemesterAt($ctx['semesters'], 1, 2);
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');

    $enrolment->update(['semester_id' => $semesterTwoId]);
    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semesterTwoId,
        ],
        [
            'programme_semester_id' => $nc1Sem2?->id,
            'student_enrolment_status_id' => $activeId,
        ],
    );

    $reason = app(StudentEnrolmentProgressionService::class)
        ->cannotAdvanceToNextPhaseReason($enrolment->fresh(['studentSemesters.semester', 'studentSemesters.programmeSemester']));

    expect($reason)->not->toBeNull();

    expect(fn () => app(AdvanceToNextSemesterAction::class)->execute($enrolment->fresh()))
        ->toThrow(StudentEnrolmentProgressionException::class);
});

it('records NC 1 complete only after both semesters across calendar years', function (): void {
    $ctx = createMultiYearNcEnrolment('STAGE-SPLIT-YEAR');
    $enrolment2026 = $ctx['enrolment'];
    $student = $enrolment2026->student;
    $stage1 = $ctx['stage1'];
    $nc1Sem1 = programmeSemesterAt($ctx['semesters'], 1, 1);
    $nc1Sem2 = programmeSemesterAt($ctx['semesters'], 1, 2);
    $proceedId = (int) StudentEnrolmentStatus::query()->where('slug', 'proceed')->value('id');
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');

    StudentSemester::query()
        ->where('student_enrolment_id', $enrolment2026->id)
        ->where('semester_id', $semesterOneId)
        ->update([
            'programme_semester_id' => $nc1Sem1?->id,
            'student_enrolment_status_id' => $proceedId,
        ]);

    $completion = app(ProgrammeStageCompletionService::class);

    expect($completion->isStageComplete($student->fresh(), $stage1))->toBeFalse();

    $calendar2028 = AcademicCalendar::query()->create([
        'calendar_year' => '2028',
        'type' => 'semester',
        'opening_date' => '2028-01-01',
        'closing_date' => '2028-12-31',
    ]);

    $enrolment2028 = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $enrolment2026->student_application_id,
        'institution_department_id' => $enrolment2026->institution_department_id,
        'department_level_id' => $enrolment2026->department_level_id,
        'department_course_id' => $enrolment2026->department_course_id,
        'programme_stage_id' => $stage1->id,
        'semester_id' => $semesterTwoId,
        'academic_calendar_id' => $calendar2028->id,
        'mode_of_study_id' => $enrolment2026->mode_of_study_id,
        'student_enrolment_status_id' => $activeId,
    ]);

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment2028->id,
            'semester_id' => $semesterTwoId,
        ],
        [
            'programme_semester_id' => $nc1Sem2?->id,
            'student_enrolment_status_id' => $proceedId,
        ],
    );

    expect($completion->isStageComplete($student->fresh(), $stage1))->toBeTrue();

    $record = $completion->recordIfComplete($student->fresh(), $stage1, $enrolment2026->studentApplication);

    expect($record)->not->toBeNull()
        ->and($record?->completed_at)->not->toBeNull()
        ->and(StudentProgrammeStage::query()
            ->where('student_id', $student->id)
            ->where('programme_stage_id', $stage1->id)
            ->whereNotNull('completed_at')
            ->exists())->toBeTrue();
});
