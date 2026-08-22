<?php

declare(strict_types=1);

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Examinations\ExaminationResult;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentExamResult;
use App\Models\Students\StudentSemester;
use App\Services\Students\StudentProgrammeDataService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

require_once __DIR__.'/../../Support/BulkFinaliseTestHelpers.php';

beforeEach(function (): void {
    foreach ([
        'Semester 1' => 'semester-1',
        'Semester 2' => 'semester-2',
        'Term 1' => 'term-1',
        'Term 2' => 'term-2',
        'Term 3' => 'term-3',
        'ABMA 1' => 'abma-1',
        'ABMA 2' => 'abma-2',
        'ABMA 3' => 'abma-3',
        'ABMA 4' => 'abma-4',
    ] as $name => $slug) {
        Semester::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Award', 'Absent', 'Deferred', 'Disqualified', 'Proceed', 'Referred', 'Unknown'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test'],
        );
    }
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

/**
 * @return array{
 *     student: Student,
 *     courseSyllabus: CourseSyllabus,
 *     semesterOneId: int,
 *     semesterTwoId: int
 * }
 */
function createProgrammeTestContext(
    string $studentNumber,
    AcademicCalendarTypeEnum $calendarType = AcademicCalendarTypeEnum::SEMESTER,
    string $semesterSlug = 'semester-1',
): array {
    $studentApplication = createVerifiedStudentApplication($studentNumber);
    $studentApplication->departmentLevel->level->update(['calendar_type' => $calendarType->value]);

    $departmentLevelCourse = $studentApplication->departmentLevel
        ->courses()
        ->firstOrFail();

    $courseSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Programme Syllabus',
        'code' => 'PROG-'.Str::upper(Str::random(4)),
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $semesterOneId = (int) Semester::query()->where('slug', 'semester-1')->value('id');
    $semesterTwoId = (int) Semester::query()->where('slug', 'semester-2')->value('id');

    CourseSyllabusModule::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'course_syllabus_id' => $courseSyllabus->id,
        'semester_id' => $semesterOneId,
        'title' => 'Semester One Module',
        'code' => 'S1-M01',
        'shared' => false,
    ]);

    CourseSyllabusModule::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'course_syllabus_id' => $courseSyllabus->id,
        'semester_id' => $semesterTwoId,
        'title' => 'Semester Two Module',
        'code' => 'S2-M01',
        'shared' => false,
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-06-30',
    ]);

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-31',
    ]);

    $statusId = (int) StudentEnrolmentStatus::query()->where('name', 'Active')->value('id');
    $semesterId = (int) Semester::query()->where('slug', $semesterSlug)->value('id');
    $calendar = AcademicCalendar::query()
        ->where('calendar_year', '2026')
        ->where('type', AcademicCalendarTypeEnum::SEMESTER->value)
        ->where('opening_date', $semesterSlug === 'semester-2' ? '2026-07-01' : '2026-01-01')
        ->firstOrFail();

    StudentEnrolment::query()->create([
        'student_id' => $studentApplication->student_id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'semester_id' => $semesterId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $statusId,
        'course_syllabus_ids' => [$courseSyllabus->id],
    ]);

    $student = Student::query()->findOrFail($studentApplication->student_id);

    return compact('student', 'courseSyllabus', 'semesterOneId', 'semesterTwoId');
}

it('lists all semester phases with modules even when only semester one is enrolled', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-S1-ONLY');
    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);

    expect($programmes)->toHaveCount(1);

    $semesters = $programmes[0]['semesters'];

    expect($semesters)->toHaveCount(2)
        ->and(collect($semesters)->pluck('label')->all())->toBe(['Semester 2', 'Semester 1'])
        ->and(collect($semesters)->firstWhere('label', 'Semester 1')['module'])->toHaveCount(1)
        ->and(collect($semesters)->firstWhere('label', 'Semester 2')['module'])->toHaveCount(1)
        ->and(collect($semesters)->firstWhere('label', 'Semester 1')['module'][0]['code'])->toBe('S1-M01')
        ->and(collect($semesters)->firstWhere('label', 'Semester 2')['module'][0]['code'])->toBe('S2-M01');
});

it('loads modules from programme syllabuses when student_semesters have no pinned course_syllabus_ids', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-09-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-PIVOT-MODULES', AcademicCalendarTypeEnum::SEMESTER, 'semester-2');
    $enrolment = StudentEnrolment::query()->where('student_id', $context['student']->id)->firstOrFail();
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');
    $proceedId = (int) StudentEnrolmentStatus::query()->where('slug', 'proceed')->value('id');

    StudentEnrolment::withoutEvents(function () use ($enrolment): void {
        $enrolment->update(['course_syllabus_ids' => null]);
    });

    StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['semesterOneId'])
        ->update([
            'student_enrolment_status_id' => $proceedId,
            'course_syllabus_ids' => null,
        ]);

    StudentSemester::query()
        ->where('student_enrolment_id', $enrolment->id)
        ->where('semester_id', $context['semesterTwoId'])
        ->update([
            'student_enrolment_status_id' => $activeId,
            'course_syllabus_ids' => null,
        ]);

    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    expect(collect($semesters)->firstWhere('label', 'Semester 1')['module'])->toHaveCount(1)
        ->and(collect($semesters)->firstWhere('label', 'Semester 1')['module'][0]['code'])->toBe('S1-M01')
        ->and(collect($semesters)->firstWhere('label', 'Semester 2')['module'])->toHaveCount(1)
        ->and(collect($semesters)->firstWhere('label', 'Semester 2')['module'][0]['code'])->toBe('S2-M01');
});

it('marks calendar-current semester as unknown without exam results in august', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-AUG');
    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');
    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');

    expect($semesters[0]['label'])->toBe('Semester 2')
        ->and($semesterTwo['isCurrent'])->toBeTrue()
        ->and($semesterTwo['status'])->toBe('Unknown')
        ->and($semesterTwo['needsResultsCollection'])->toBeFalse()
        ->and($semesterOne['isCurrent'])->toBeFalse()
        ->and($semesterOne['status'])->toBe('Unknown')
        ->and($semesterOne['needsResultsCollection'])->toBeTrue();
});

it('keeps semester two first but marks semester one unknown in march without exam results', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-MAR');
    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');
    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');

    expect($semesters[0]['label'])->toBe('Semester 2')
        ->and($semesterOne['isCurrent'])->toBeTrue()
        ->and($semesterOne['status'])->toBe('Unknown')
        ->and($semesterOne['needsResultsCollection'])->toBeFalse()
        ->and($semesterTwo['isCurrent'])->toBeFalse()
        ->and($semesterTwo['status'])->toBe('Unknown')
        ->and($semesterTwo['needsResultsCollection'])->toBeTrue();
});

it('lists three term phases for term calendar types', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $studentApplication = createVerifiedStudentApplication('PROG-TERM');
    $studentApplication->departmentLevel->level->update(['calendar_type' => AcademicCalendarTypeEnum::TERM->value]);

    foreach ([
        ['2026-01-01', '2026-04-30'],
        ['2026-05-01', '2026-08-31'],
        ['2026-09-01', '2026-12-31'],
    ] as [$opening, $closing]) {
        AcademicCalendar::query()->create([
            'calendar_year' => '2026',
            'type' => AcademicCalendarTypeEnum::TERM->value,
            'opening_date' => $opening,
            'closing_date' => $closing,
        ]);
    }

    $statusId = (int) StudentEnrolmentStatus::query()->where('name', 'Active')->value('id');
    $termOneId = (int) Semester::query()->where('slug', 'term-1')->value('id');
    $calendar = AcademicCalendar::query()
        ->where('calendar_year', '2026')
        ->where('type', AcademicCalendarTypeEnum::TERM->value)
        ->orderBy('opening_date')
        ->firstOrFail();

    StudentEnrolment::query()->create([
        'student_id' => $studentApplication->student_id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'semester_id' => $termOneId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $statusId,
    ]);

    $student = Student::query()->findOrFail($studentApplication->student_id);
    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($student);

    expect($programmes[0]['semesters'])->toHaveCount(3)
        ->and(collect($programmes[0]['semesters'])->pluck('label')->all())->toBe(['Term 3', 'Term 2', 'Term 1']);
});

it('lists four abma phases for abma calendar types', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $studentApplication = createVerifiedStudentApplication('PROG-ABMA');
    $studentApplication->departmentLevel->level->update(['calendar_type' => AcademicCalendarTypeEnum::ABMA->value]);

    foreach ([
        '2026-01-01',
        '2026-04-01',
        '2026-07-01',
        '2026-10-01',
    ] as $opening) {
        AcademicCalendar::query()->create([
            'calendar_year' => '2026',
            'type' => AcademicCalendarTypeEnum::ABMA->value,
            'opening_date' => $opening,
            'closing_date' => Carbon::parse($opening)->addMonths(2)->toDateString(),
        ]);
    }

    $statusId = (int) StudentEnrolmentStatus::query()->where('name', 'Active')->value('id');
    $abmaOneId = (int) Semester::query()->where('slug', 'abma-1')->value('id');
    $calendar = AcademicCalendar::query()
        ->where('calendar_year', '2026')
        ->where('type', AcademicCalendarTypeEnum::ABMA->value)
        ->orderBy('opening_date')
        ->firstOrFail();

    StudentEnrolment::query()->create([
        'student_id' => $studentApplication->student_id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'semester_id' => $abmaOneId,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $statusId,
    ]);

    $student = Student::query()->findOrFail($studentApplication->student_id);
    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($student);

    expect($programmes[0]['semesters'])->toHaveCount(4)
        ->and(collect($programmes[0]['semesters'])->pluck('label')->all())->toBe(['ABMA 4', 'ABMA 3', 'ABMA 2', 'ABMA 1']);
});

it('shows exam result PROCEED on semester 1 and marks semester 2 active', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-PROCEED');
    $enrolment = StudentEnrolment::query()
        ->where('student_id', $context['student']->id)
        ->firstOrFail();

    StudentExamResult::query()->create([
        'tenant_id' => $enrolment->institution_department_id,
        'student_id' => $context['student']->id,
        'candidate_number' => 'CAND-PROCEED',
        'department_level_id' => $enrolment->department_level_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'PROCEED',
    ]);

    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');
    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');

    $proceedId = (int) StudentEnrolmentStatus::query()->where('slug', 'proceed')->value('id');
    $activeId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    expect($semesterOne['hasExamResult'])->toBeTrue()
        ->and($semesterOne['examResultStatus'])->toBe('PROCEED')
        ->and($semesterOne['status'])->toBe('Proceed')
        ->and($semesterOne['isDisabled'])->toBeFalse()
        ->and($semesterTwo['isDisabled'])->toBeFalse()
        ->and($semesterTwo['status'])->toBe('Active')
        ->and($semesterTwo['statusSlug'])->toBe('active')
        ->and((int) StudentSemester::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('semester_id', $context['semesterOneId'])
            ->value('student_enrolment_status_id'))->toBe($proceedId)
        ->and((int) StudentSemester::query()
            ->where('student_enrolment_id', $enrolment->id)
            ->where('semester_id', $context['semesterTwoId'])
            ->value('student_enrolment_status_id'))->toBe($activeId);
});

it('shows exam result AWARD on last semester and marks level completable', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-AWARD', AcademicCalendarTypeEnum::SEMESTER, 'semester-2');
    $enrolment = StudentEnrolment::query()
        ->where('student_id', $context['student']->id)
        ->firstOrFail();

    StudentExamResult::query()->create([
        'tenant_id' => $enrolment->institution_department_id,
        'student_id' => $context['student']->id,
        'candidate_number' => 'CAND-AWARD',
        'department_level_id' => $enrolment->department_level_id,
        'calendar_year' => 2026,
        'session' => '2026-12-01',
        'comment' => 'AWARD',
    ]);

    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');

    expect($semesterTwo['hasExamResult'])->toBeTrue()
        ->and($semesterTwo['examResultStatus'])->toBe('AWARD')
        ->and($semesterTwo['status'])->toBe('Award')
        ->and($semesterTwo['canCompleteLevel'])->toBeTrue();
});

it('shows exam result ABSENT on semester 1 and disables semester 2', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-ABSENT');
    $enrolment = StudentEnrolment::query()
        ->where('student_id', $context['student']->id)
        ->firstOrFail();

    StudentExamResult::query()->create([
        'tenant_id' => $enrolment->institution_department_id,
        'student_id' => $context['student']->id,
        'candidate_number' => 'CAND-ABSENT',
        'department_level_id' => $enrolment->department_level_id,
        'calendar_year' => 2026,
        'session' => '2026-06-01',
        'comment' => 'ABSENT',
    ]);

    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');
    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');

    expect($semesterOne['hasExamResult'])->toBeTrue()
        ->and($semesterOne['examResultStatus'])->toBe('ABSENT')
        ->and($semesterOne['isDisabled'])->toBeFalse()
        ->and($semesterTwo['isDisabled'])->toBeTrue();
});

it('shows availableStatuses and hasExamResult false when no exam result exists', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-NO-EXAM');
    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');

    expect($semesterOne['hasExamResult'])->toBeFalse()
        ->and($semesterOne['examResultStatus'])->toBeNull()
        ->and($semesterOne['availableStatuses'])->toBeArray()
        ->and(count($semesterOne['availableStatuses']))->toBeGreaterThan(0);
});

it('populates exam grade and session on modules when ExaminationResult rows exist', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-GRADES');
    $enrolment = StudentEnrolment::query()
        ->where('student_id', $context['student']->id)
        ->firstOrFail();

    $session = '2026-06-01';

    StudentExamResult::query()->create([
        'tenant_id' => $enrolment->institution_department_id,
        'student_id' => $context['student']->id,
        'candidate_number' => 'CAND-GRADES',
        'department_level_id' => $enrolment->department_level_id,
        'calendar_year' => 2026,
        'session' => $session,
        'comment' => 'PROCEED',
    ]);

    ExaminationResult::query()->create([
        'tenant_id' => $context['student']->tenant_id,
        'student_id' => null,
        'candidate_number' => 'CAND-GRADES',
        'subject_code' => 'S1-M01',
        'subject' => 'Semester One Module',
        'grade' => 'B+',
        'session' => $session,
    ]);

    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');
    $module = $semesterOne['module'][0] ?? null;

    expect($module)->not->toBeNull()
        ->and($module['code'])->toBe('S1-M01')
        ->and($module['grade'])->toBe('B+')
        ->and($module['examSession'])->toBe($session);

    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');
    $module2 = $semesterTwo['module'][0] ?? null;

    expect($module2)->not->toBeNull()
        ->and($module2['grade'])->toBeNull()
        ->and($module2['examSession'])->toBeNull();
});

it('resolves deferred module grades by candidate number and session without relying on student id', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-08-15', config('app.timezone')));

    $context = createProgrammeTestContext('PROG-DEFERRED-GRADES');
    $enrolment = StudentEnrolment::query()
        ->where('student_id', $context['student']->id)
        ->firstOrFail();

    $session = '2026-06-01';

    StudentExamResult::query()->create([
        'tenant_id' => $context['student']->tenant_id,
        'student_id' => $context['student']->id,
        'candidate_number' => 'CAND-DEFERRED',
        'department_level_id' => $enrolment->department_level_id,
        'calendar_year' => 2026,
        'session' => $session,
        'comment' => 'DEFERRED',
    ]);

    ExaminationResult::query()->create([
        'tenant_id' => $context['student']->tenant_id,
        'student_id' => null,
        'candidate_number' => 'CAND-DEFERRED',
        'subject_code' => 'S1-M01',
        'subject' => 'Semester One Module',
        'grade' => 'F',
        'session' => $session,
    ]);

    $programmes = app(StudentProgrammeDataService::class)->buildProgrammesForStudent($context['student']);
    $semesters = $programmes[0]['semesters'];

    $semesterOne = collect($semesters)->firstWhere('label', 'Semester 1');
    $semesterTwo = collect($semesters)->firstWhere('label', 'Semester 2');
    $module = $semesterOne['module'][0] ?? null;

    expect($semesterOne['status'])->toBe('Deferred')
        ->and($module)->not->toBeNull()
        ->and($module['grade'])->toBe('F')
        ->and($module['examSession'])->toBe($session)
        ->and($semesterTwo['isDisabled'])->toBeTrue();
});
