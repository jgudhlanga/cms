<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Mail\Enrolments\StudentEnrollmentExportMail;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\NextOfKin;
use App\Models\Shared\Relationship;
use App\Models\Students\Sponsor;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Services\Enrolments\StudentEnrollmentExportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', config('app.timezone')));

    AcademicCalendar::query()->firstOrCreate(
        [
            'calendar_year' => '2025/2026',
            'type' => 'semester',
        ],
        [
            'opening_date' => '2026-01-01',
            'closing_date' => '2026-12-31',
        ],
    );

    foreach (['Semester 1', 'Semester 2'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    foreach (['Active', 'Completed'] as $name) {
        StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => $name],
            ['description' => 'Test', 'slug' => Str::slug($name)],
        );
    }
});

afterEach(function (): void {
    Carbon::setTestNow(null);
});

it('exports finalised student enrolments to Student_Enrollment.csv', function (): void {
    Mail::fake();
    Storage::fake('local');
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $studentApplication = createVerifiedStudentApplication('EXP-001');
    createEnrolledDepartmentStep($studentApplication);
    createBankCreditReceipt('EXP-001', '2026-01-10 09:00:00', 'TXN-EXP-001');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    $student = $studentApplication->student()->firstOrFail();
    $student->update(['id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id()]);
    $tenantId = $studentApplication->tenant_id;

    Address::query()->create([
        'tenant_id' => $tenantId,
        'addressable_id' => $student->id,
        'addressable_type' => $student->getMorphClass(),
        'address_1' => '12',
        'address_2' => 'Main Street',
        'address_3' => 'Harare',
        'address_is_main' => true,
    ]);

    $relationship = Relationship::query()->firstOrCreate(['name' => 'Parent']);

    $nextOfKin = NextOfKin::query()->forceCreate([
        'tenant_id' => $tenantId,
        'kinnable_id' => $student->id,
        'kinnable_type' => $student->getMorphClass(),
        'name' => 'Jane Guardian',
        'relationship_id' => $relationship->id,
    ]);

    Contact::query()->create([
        'tenant_id' => $tenantId,
        'contactable_id' => $nextOfKin->id,
        'contactable_type' => $nextOfKin->getMorphClass(),
        'phone_number' => '0771234567',
        'email_address' => 'jane@example.com',
    ]);

    Address::query()->create([
        'tenant_id' => $tenantId,
        'addressable_id' => $nextOfKin->id,
        'addressable_type' => $nextOfKin->getMorphClass(),
        'address_1' => '45',
        'address_2' => 'Second Street',
        'address_3' => 'Bulawayo',
        'address_is_main' => true,
    ]);

    Sponsor::query()->create([
        'tenant_id' => $tenantId,
        'student_id' => $student->id,
        'name' => 'ACME Bursary',
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()
        ->where('department_level_id', $studentApplication->department_level_id)
        ->where('department_course_id', $studentApplication->department_course_id)
        ->firstOrFail();

    CourseSyllabus::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'institution_department_id' => $studentApplication->institution_department_id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Export Test Syllabus',
        'code' => 'ICT101',
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    expect(ClassList::query()->where('student_application_id', $studentApplication->id)->value('type'))
        ->toBe(ClassListTypeEnum::FINAL)
        ->and(StudentEnrolment::query()->where('student_application_id', $studentApplication->id)->exists())
        ->toBeTrue();

    $this->artisan('enrolments:export-student-enrollment --sync --email=one@example.test,two@example.test')
        ->assertSuccessful();

    Storage::disk('local')->assertExists(StudentEnrollmentExportService::OUTPUT_PATH);

    $handle = fopen(Storage::disk('local')->path(StudentEnrollmentExportService::OUTPUT_PATH), 'r');
    $header = fgetcsv($handle);
    $row = fgetcsv($handle);
    fclose($handle);

    expect($header)->toBe(StudentEnrollmentExportService::HEADERS)
        ->and($row[0])->toBe('EXP-001')
        ->and($row[1])->toBe('Test Bulk')
        ->and($row[2])->toBe('Student')
        ->and($row[3])->toBe($student->id_number)
        ->and($row[4])->toBe('Male')
        ->and($row[5])->toBe('01/01/2001')
        ->and($row[6])->toBe('Harare')
        ->and($row[7])->toBe('')
        ->and($row[8])->toBe('Main Street')
        ->and($row[9])->toBe('12')
        ->and($row[10])->toBe('ICT101')
        ->and($row[11])->toBe('2025/2026')
        ->and($row[12])->toBe('Full Time')
        ->and($row[13])->toBe('2025/2026')
        ->and($row[14])->toBe('Semester 1')
        ->and($row[15])->toBe('No')
        ->and($row[16])->toBe('Yes')
        ->and($row[17])->toBe('ACME Bursary')
        ->and($row[18])->toBe('Parent')
        ->and($row[19])->toBe('Parent')
        ->and($row[20])->toBe('Jane Guardian')
        ->and($row[21])->toBe('jane@example.com')
        ->and($row[22])->toBe('0771234567')
        ->and($row[23])->toBe('45, Second Street, Bulawayo');

    Mail::assertSent(StudentEnrollmentExportMail::class, function (StudentEnrollmentExportMail $mail): bool {
        $attachments = $mail->attachments();

        return $mail->hasTo('one@example.test')
            && $mail->hasTo('two@example.test')
            && count($attachments) === 1;
    });
});

it('filters export rows by intake year when the option is provided', function (): void {
    Storage::fake('local');
    config()->set('custom.bank-statements.plan_anchor_start', '2026-01-01');

    $matchingProgram = createVerifiedStudentApplication('EXP-MATCH');
    createEnrolledDepartmentStep($matchingProgram);
    createBankCreditReceipt('EXP-MATCH', '2026-01-10 09:00:00', 'TXN-EXP-MATCH');

    $otherProgram = createVerifiedStudentApplication('EXP-OTHER');
    createEnrolledDepartmentStep($otherProgram);
    createBankCreditReceipt('EXP-OTHER', '2026-01-10 09:00:00', 'TXN-EXP-OTHER');

    $this->artisan('enrolments:bulk-finalise-enrolments-command')->assertSuccessful();

    $otherProgram->intakePeriod()->update(['calendar_year' => '2024/2025']);

    $this->artisan('enrolments:export-student-enrollment --sync --intake-year=2025/2026 --email=exports@example.test')
        ->assertSuccessful();

    $handle = fopen(Storage::disk('local')->path(StudentEnrollmentExportService::OUTPUT_PATH), 'r');
    fgetcsv($handle);
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][0])->toBe('EXP-MATCH');
});
