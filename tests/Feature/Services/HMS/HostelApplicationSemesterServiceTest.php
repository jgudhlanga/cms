<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Students\StudentApplication;
use App\Services\HMS\HostelApplicationSemesterService;
use Illuminate\Support\Carbon;

it('resolves running semester dates for a student enrolment', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    $studentApplication = createStudentReadyForHostelApplication('SEM-SERVICE-001');
    $calendar = AcademicCalendar::query()
        ->where('calendar_year', '2025/2026')
        ->where('type', AcademicCalendarTypeEnum::SEMESTER)
        ->firstOrFail();

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student);

    expect($result['success'])->toBeTrue()
        ->and($result['checkIn'])->toBe(Carbon::parse($calendar->opening_date)->toDateString())
        ->and($result['checkOut'])->toBe(Carbon::parse($calendar->closing_date)->toDateString())
        ->and($result['label'])->toBe('Semester 1');
});

it('returns no running semester blocker when calendar is outside application date', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-15', config('app.timezone')));

    createStudentReadyForHostelApplication('SEM-SERVICE-002', withRunningSemester: false);

    AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => '2025-01-01',
        'closing_date' => '2025-06-30',
    ]);

    $studentApplication = StudentApplication::query()
        ->whereHas('student', fn ($q) => $q->where('student_number', 'SEM-SERVICE-002'))
        ->firstOrFail();

    $service = app(HostelApplicationSemesterService::class);
    $result = $service->datesForApplication($studentApplication->student);

    expect($result['success'])->toBeFalse()
        ->and($result['blocker'])->toBe(HostelApplicationSemesterService::BLOCKER_NO_RUNNING_SEMESTER);
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});
