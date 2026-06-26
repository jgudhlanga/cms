<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentApplication;
use App\Services\Students\StudentPortalTermDetailsService;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function (): void {
    foreach (['Term 1', 'Term 2', 'Semester 1', 'Semester 2'] as $name) {
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

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-TERM', 'term', '2026');
    $student = $studentApplication->student;

    $termOneOptionId = (int) AcademicYearOption::query()->where('slug', 'term-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'academic_year_option_id' => $termOneOptionId,
        'academic_calendar_id' => $termOne->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $result = app(StudentPortalTermDetailsService::class)->build($student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['calendarType'])->toBe('term')
        ->and($result['currentTerm']['label'])->toBe('Term 1')
        ->and($result['currentTerm']['openingDate'])->toBe('2026-02-03')
        ->and($result['currentTerm']['closingDate'])->toBe('2026-04-30')
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

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-TERM-REMAP', 'term', '2026');
    $student = $studentApplication->student;

    $semesterOneOptionId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    $enrolment = StudentEnrolment::query()->create([
        'student_id' => $student->id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'academic_year_option_id' => $semesterOneOptionId,
        'academic_calendar_id' => $semesterCalendar->id,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);

    $result = app(StudentPortalTermDetailsService::class)->build($student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['calendarType'])->toBe('term')
        ->and($result['currentTerm']['label'])->toBe('Term 1')
        ->and($result['currentTerm']['openingDate'])->toBe('2026-02-03');
});

test('portal term details resolves semester calendars from intake calendar year and today', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-SEM', 'semester', '2025/2026');
    $enrolment = createPortalTermDetailsEnrolment($studentApplication);

    $result = app(StudentPortalTermDetailsService::class)->build($studentApplication->student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['calendarType'])->toBe('semester')
        ->and($result['currentTerm']['label'])->toBe('Semester 1')
        ->and($result['currentTerm']['openingDate'])->toBe('2026-01-15')
        ->and($result['currentTerm']['closingDate'])->toBe('2026-06-30')
        ->and($result['nextTerm']['label'])->toBe('Semester 2')
        ->and($result['nextTerm']['openingDate'])->toBe('2026-07-01');
});

test('portal term details keeps current semester during inter-semester gap', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-05-15', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-07-01',
        'closing_date' => '2026-12-15',
    ]);

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-SEM-GAP', 'semester', '2025/2026');
    $enrolment = createPortalTermDetailsEnrolment($studentApplication);

    $result = app(StudentPortalTermDetailsService::class)->build($studentApplication->student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['currentTerm']['label'])->toBe('Semester 1')
        ->and($result['currentTerm']['closingDate'])->toBe('2026-06-30')
        ->and($result['nextTerm']['label'])->toBe('Semester 2');
});

test('portal term details returns upcoming semester before academic year starts', function (): void {
    Carbon::setTestNow(Carbon::parse('2025-11-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-04-30',
    ]);

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-SEM-PRE', 'semester', '2025/2026');
    $enrolment = createPortalTermDetailsEnrolment($studentApplication);

    $result = app(StudentPortalTermDetailsService::class)->build($studentApplication->student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['currentTerm'])->toBeNull()
        ->and($result['nextTerm']['label'])->toBe('Semester 1')
        ->and($result['nextTerm']['openingDate'])->toBe('2026-01-15');
});

test('portal term details ignores calendars from a different calendar year label', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-01-15',
        'closing_date' => '2026-12-15',
    ]);

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-SEM-YEAR', 'semester', '2025/2026');
    $enrolment = createPortalTermDetailsEnrolment(
        $studentApplication,
        (int) AcademicCalendar::query()->where('calendar_year', '2026')->value('id'),
    );

    $result = app(StudentPortalTermDetailsService::class)->build($studentApplication->student, [
        'studentEnrolmentId' => $enrolment->id,
    ]);

    expect($result['currentTerm'])->toBeNull()
        ->and($result['nextTerm'])->toBeNull();
});

test('portal term details resolves semesters for applicant without enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-03',
        'closing_date' => '2026-04-30',
    ]);
    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-08-03',
        'closing_date' => '2026-12-04',
    ]);

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-APPLICANT', 'semester', '2026');
    $student = $studentApplication->student;

    $result = app(StudentPortalTermDetailsService::class)->build($student, ['module' => []]);

    expect($result['calendarType'])->toBe('semester')
        ->and($result['currentTerm']['label'])->toBe('Semester 1')
        ->and($result['currentTerm']['openingDate'])->toBe('2026-02-03')
        ->and($result['currentTerm']['closingDate'])->toBe('2026-08-02')
        ->and($result['nextTerm']['label'])->toBe('Semester 2')
        ->and($result['nextTerm']['openingDate'])->toBe('2026-08-03');
});

test('portal term details returns null when applicant has no intake calendar year', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-01', config('app.timezone')));

    AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2026-02-03',
        'closing_date' => '2026-12-04',
    ]);

    $studentApplication = createPortalTermDetailsStudentApplication('PORTAL-APPLICANT-NO-YEAR', 'semester', '2026');
    $studentApplication->intakePeriod()->update(['calendar_year' => null]);
    $student = $studentApplication->student->fresh();

    $result = app(StudentPortalTermDetailsService::class)->build($student, ['module' => []]);

    expect($result['currentTerm'])->toBeNull()
        ->and($result['nextTerm'])->toBeNull();
});

function createPortalTermDetailsStudentApplication(string $studentNumber, string $calendarType, string $calendarYear): StudentApplication
{
    $studentApplication = createVerifiedStudentApplication($studentNumber);

    $studentApplication->intakePeriod()->update([
        'calendar_year' => $calendarYear,
    ]);

    $studentApplication->departmentLevel->level->update([
        'calendar_type' => $calendarType,
    ]);

    return $studentApplication->fresh(['student', 'departmentLevel.level', 'intakePeriod']);
}

function createPortalTermDetailsEnrolment(StudentApplication $studentApplication, ?int $academicCalendarId = null): StudentEnrolment
{
    $semesterOneOptionId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');
    $activeStatusId = (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id');

    if ($academicCalendarId === null) {
        $academicCalendarId = (int) AcademicCalendar::query()->value('id');
    }

    return StudentEnrolment::query()->create([
        'student_id' => $studentApplication->student_id,
        'student_application_id' => $studentApplication->id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_id' => $studentApplication->department_level_id,
        'department_course_id' => $studentApplication->department_course_id,
        'academic_year_option_id' => $semesterOneOptionId,
        'academic_calendar_id' => $academicCalendarId,
        'mode_of_study_id' => $studentApplication->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);
}
