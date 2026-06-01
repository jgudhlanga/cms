<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
use App\Services\Students\StudentPortalTermDetailsService;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['Term 1', 'Term 2', 'Semester 1'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

test('portal term details uses term calendars and year options for term-based courses', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $termOne = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-02-03',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-05-01',
        'closing_date' => '2026-08-30',
    ]);

    $studentProgram = createPortalTermDetailsStudentProgram('PORTAL-TERM', 'term', '2026');
    $student = $studentProgram->student;

    $termOneOptionId = (int) AcademicYearOption::query()->where('slug', 'term-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $studentProgram->institution_department_id,
        'department_level_id' => $studentProgram->department_level_id,
        'department_course_id' => $studentProgram->department_course_id,
        'academic_year_option_id' => $termOneOptionId,
        'academic_calendar_id' => $termOne->id,
        'mode_of_study_id' => $studentProgram->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $result = app(StudentPortalTermDetailsService::class)->build($student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['calendarType'])->toBe('term')
        ->and($result['currentTerm']['label'])->toBe('Term 1')
        ->and($result['currentTerm']['openingDate'])->toBe('2026-02-03')
        ->and($result['nextTerm']['label'])->toBe('Term 2')
        ->and($result['nextTerm']['openingDate'])->toBe('2026-05-01');
});

test('portal term details remaps semester enrolment to term calendars when course is term-based', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    $semesterCalendar = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-03',
        'closing_date' => '2026-06-05',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM,
        'opening_date' => '2026-02-03',
        'closing_date' => '2026-04-30',
    ]);

    $studentProgram = createPortalTermDetailsStudentProgram('PORTAL-TERM-REMAP', 'term', '2026');
    $student = $studentProgram->student;

    $semesterOneOptionId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $studentProgram->institution_department_id,
        'department_level_id' => $studentProgram->department_level_id,
        'department_course_id' => $studentProgram->department_course_id,
        'academic_year_option_id' => $semesterOneOptionId,
        'academic_calendar_id' => $semesterCalendar->id,
        'mode_of_study_id' => $studentProgram->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $result = app(StudentPortalTermDetailsService::class)->build($student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['calendarType'])->toBe('term')
        ->and($result['currentTerm']['label'])->toBe('Term 1')
        ->and($result['currentTerm']['openingDate'])->toBe('2026-02-03');
});

function createPortalTermDetailsStudentProgram(string $studentNumber, string $calendarType, string $calendarYear): StudentProgram
{
    $studentProgram = createVerifiedStudentProgram($studentNumber);

    $studentProgram->intakePeriod()->update([
        'calendar_year' => $calendarYear,
    ]);

    $studentProgram->departmentLevel->level->update([
        'calendar_type' => $calendarType,
    ]);

    return $studentProgram->fresh(['student', 'departmentLevel.level', 'intakePeriod']);
}
