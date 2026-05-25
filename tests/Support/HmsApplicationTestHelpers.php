<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\HMS\HmsSetting;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoom;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;

function createRunningSemesterCalendar(string $calendarYear = '2025/2026'): AcademicCalendar
{
    return AcademicCalendar::query()->create([
        'calendar_year' => $calendarYear,
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => now()->subMonth()->toDateString(),
        'closing_date' => now()->addMonths(5)->toDateString(),
    ]);
}

function createPastSemesterCalendar(string $calendarYear = '2025/2026'): AcademicCalendar
{
    return AcademicCalendar::query()->create([
        'calendar_year' => $calendarYear,
        'type' => AcademicCalendarTypeEnum::SEMESTER,
        'opening_date' => now()->subYears(2)->toDateString(),
        'closing_date' => now()->subYear()->toDateString(),
    ]);
}

function ensureHostelRoomWithCapacity(string $hostelName, string $roomName = 'TEST-01'): HostelRoom
{
    $tenantId = TenantEnum::HARARE_POLY->id();

    $hostel = Hostel::query()->firstOrCreate(
        ['name' => $hostelName],
        [
            'tenant_id' => $tenantId,
            'floor_count' => 1,
            'rooms_count' => 1,
            'capacity' => 2,
            'status' => 'active',
            'type' => null,
            'location' => null,
            'warden_id' => null,
            'description' => null,
        ],
    );

    return HostelRoom::query()->firstOrCreate(
        ['name' => $roomName, 'hostel_id' => $hostel->id],
        [
            'tenant_id' => $tenantId,
            'room_type' => 'double',
            'capacity' => 2,
            'max_occupancy' => 2,
            'current_occupancy' => 0,
            'status' => 'vacant',
            'floor_number' => 0,
            'description' => null,
        ],
    );
}

function attachHostelApplicationEnrolment(
    StudentProgram $studentProgram,
    ?AcademicCalendar $calendar = null,
): StudentEnrolment {
    $calendarYear = (string) ($studentProgram->intakePeriod?->calendar_year ?? '2025/2026');
    $calendar ??= createRunningSemesterCalendar($calendarYear);

    $activeStatusId = (int) StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    )->id;

    $semesterOptionId = (int) AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    )->id;

    return StudentEnrolment::query()->create([
        'student_id' => $studentProgram->student_id,
        'student_program_id' => $studentProgram->id,
        'institution_department_id' => $studentProgram->institution_department_id,
        'department_level_id' => $studentProgram->department_level_id,
        'department_course_id' => $studentProgram->department_course_id,
        'academic_calendar_id' => $calendar->id,
        'academic_year_option_id' => $semesterOptionId,
        'mode_of_study_id' => $studentProgram->mode_of_study_id,
        'student_enrolment_status_id' => $activeStatusId,
    ]);
}

function disableAllHmsApprovalRequirements(int $tenantId): HmsSetting
{
    $settings = HmsSetting::resolveForTenant($tenantId);

    $settings->update([
        'require_full_time_study' => false,
        'require_tuition_paid' => false,
        'require_accommodation_paid' => false,
        'require_address_outside_campus' => false,
    ]);

    return $settings->fresh();
}

function createStudentReadyForHostelApplication(
    string $studentNumber,
    bool $withRunningSemester = true,
): StudentProgram {
    $studentProgram = createVerifiedStudentProgram($studentNumber);

    $calendarYear = (string) ($studentProgram->intakePeriod?->calendar_year ?? '2025/2026');
    $calendar = $withRunningSemester
        ? createRunningSemesterCalendar($calendarYear)
        : createPastSemesterCalendar($calendarYear);

    attachHostelApplicationEnrolment($studentProgram, $calendar);

    return $studentProgram->fresh(['student', 'intakePeriod']);
}
