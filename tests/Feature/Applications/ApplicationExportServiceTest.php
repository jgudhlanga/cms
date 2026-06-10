<?php

use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Mail\Applications\ApplicationExportMail;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\Country;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Queries\Applications\ApplicationExportQuery;
use App\Services\Applications\ApplicationExportService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

it('exports accepted and enrolled applications to Application.csv', function (): void {
    Mail::fake();
    Storage::fake('local');

    $program = createVerifiedStudentProgram('APP-ONLY');
    $student = $program->student()->firstOrFail();
    $student->update([
        'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
    ]);

    $country = Country::factory()->create(['name' => 'Zimbabwe']);
    $student->update(['country_id' => $country->id]);

    Address::query()->create([
        'tenant_id' => $program->tenant_id,
        'addressable_id' => $student->id,
        'addressable_type' => $student->getMorphClass(),
        'address_3' => 'Harare',
        'address_is_main' => true,
    ]);

    Contact::query()->create([
        'tenant_id' => $program->tenant_id,
        'contactable_id' => $student->id,
        'contactable_type' => $student->getMorphClass(),
        'phone_number' => '0771111111',
        'email_address' => 'student@example.com',
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()
        ->where('department_level_id', $program->department_level_id)
        ->where('department_course_id', $program->department_course_id)
        ->firstOrFail();

    CourseSyllabus::query()->create([
        'tenant_id' => $program->tenant_id,
        'institution_department_id' => $program->institution_department_id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Application Export Syllabus',
        'code' => 'ICT101',
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $this->artisan('applications:export --sync --email=exports@example.test')
        ->assertSuccessful();

    Storage::disk('local')->assertExists(ApplicationExportService::OUTPUT_PATH);

    $handle = fopen(Storage::disk('local')->path(ApplicationExportService::OUTPUT_PATH), 'r');
    $header = fgetcsv($handle);
    $row = fgetcsv($handle);
    fclose($handle);

    expect($header)->toBe(ApplicationExportService::HEADERS)
        ->and($row[0])->toBe($student->id_number)
        ->and($row[1])->toBe('No')
        ->and($row[2])->toBe('Test Bulk')
        ->and($row[3])->toBe('Student')
        ->and($row[4])->toBe('Male')
        ->and($row[5])->toBe('01/01/2001')
        ->and($row[6])->toBe('Zimbabwe')
        ->and($row[7])->toBe('Zimbabwe')
        ->and($row[8])->toBe('0771111111')
        ->and($row[9])->toBe($student->user->email)
        ->and($row[10])->toBe('Harare')
        ->and($row[11])->toBe('ICT101')
        ->and($row[12])->toBe('1')
        ->and($row[13])->toBe('Yes')
        ->and($row[14])->toBe('Full Time')
        ->and($row[15])->toBe('Full Time');

    Mail::assertSent(ApplicationExportMail::class, function (ApplicationExportMail $mail): bool {
        return $mail->hasTo('exports@example.test')
            && count($mail->attachments()) === 1;
    });
});

it('prefers enrolled programmes over accepted when deduplicating by student', function (): void {
    Storage::fake('local');

    $enrolledProgram = createVerifiedStudentProgram('APP-DEDUP');
    $enrolledStep = createEnrolledDepartmentStep($enrolledProgram);
    $enrolledProgram->update(['department_application_step_id' => $enrolledStep->id]);

    $enrolledDepartmentLevelCourse = DepartmentLevelCourse::query()
        ->where('department_level_id', $enrolledProgram->department_level_id)
        ->where('department_course_id', $enrolledProgram->department_course_id)
        ->firstOrFail();

    CourseSyllabus::query()->create([
        'tenant_id' => $enrolledProgram->tenant_id,
        'institution_department_id' => $enrolledProgram->institution_department_id,
        'department_level_course_id' => $enrolledDepartmentLevelCourse->id,
        'title' => 'Enrolled Syllabus',
        'code' => 'ICT101',
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $acceptedProgram = createAdditionalStudentProgramForStudent(
        $enrolledProgram->student()->firstOrFail(),
        $enrolledProgram,
        WorkflowStepEnum::ACCEPTED,
        'ICT202',
    );

    $this->artisan('applications:export --sync --email=exports@example.test')
        ->assertSuccessful();

    $handle = fopen(Storage::disk('local')->path(ApplicationExportService::OUTPUT_PATH), 'r');
    fgetcsv($handle);
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][11])->toBe('ICT101')
        ->and($acceptedProgram->id)->not->toBe($enrolledProgram->id);
});

it('matches application export row count with the export query count', function (): void {
    Storage::fake('local');

    $enrolledProgram = createVerifiedStudentProgram('APP-COUNT');
    $enrolledStep = createEnrolledDepartmentStep($enrolledProgram);
    $enrolledProgram->update(['department_application_step_id' => $enrolledStep->id]);

    createAdditionalStudentProgramForStudent(
        $enrolledProgram->student()->firstOrFail(),
        $enrolledProgram,
        WorkflowStepEnum::ACCEPTED,
        'ICT202',
    );

    createVerifiedStudentProgram('APP-COUNT-OTHER');

    $exportCount = app(ApplicationExportQuery::class)->count();

    $this->artisan('applications:export --sync --email=exports@example.test')
        ->assertSuccessful();

    $handle = fopen(Storage::disk('local')->path(ApplicationExportService::OUTPUT_PATH), 'r');
    fgetcsv($handle);
    $rowCount = 0;

    while (fgetcsv($handle) !== false) {
        $rowCount++;
    }

    fclose($handle);

    expect($exportCount)->toBe($rowCount);
});

it('filters application export rows by intake year when the option is provided', function (): void {
    Storage::fake('local');

    $matchingProgram = createVerifiedStudentProgram('APP-MATCH');
    $otherProgram = createVerifiedStudentProgram('APP-OTHER');
    $otherProgram->intakePeriod()->update(['calendar_year' => '2024/2025']);

    $this->artisan('applications:export --sync --intake-year=2025/2026 --email=exports@example.test')
        ->assertSuccessful();

    $handle = fopen(Storage::disk('local')->path(ApplicationExportService::OUTPUT_PATH), 'r');
    fgetcsv($handle);
    $rows = [];

    while (($row = fgetcsv($handle)) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    expect($rows)->toHaveCount(1)
        ->and($rows[0][2])->toBe('Test Bulk');
});

function createAdditionalStudentProgramForStudent(
    Student $student,
    StudentProgram $template,
    WorkflowStepEnum $workflowStep,
    string $syllabusCode,
): StudentProgram {
    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $template->tenant_id,
        'department_id' => $department->id,
        'department_code' => 'alt-'.Str::lower(Str::random(6)),
        'description' => 'Alternate department for application export tests',
    ]);
    $course = Course::factory()->create();
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $template->tenant_id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create([
        'name' => 'Alt Level '.Str::upper(Str::random(5)),
        'calendar_type' => 'semester',
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $template->tenant_id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);
    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    CourseSyllabus::query()->create([
        'tenant_id' => $template->tenant_id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Alternate Syllabus',
        'code' => $syllabusCode,
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $departmentStep = resolveDepartmentApplicationStep(
        StudentProgram::query()->make([
            'tenant_id' => $template->tenant_id,
            'institution_department_id' => $institutionDepartment->id,
        ]),
        $workflowStep,
    );

    return StudentProgram::query()->create([
        'tenant_id' => $template->tenant_id,
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartment->id,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $template->intake_period_id,
        'mode_of_study_id' => $template->mode_of_study_id,
        'application_tracking_number' => 'APP-'.strtoupper(str()->random(8)),
        'department_application_step_id' => $departmentStep->id,
    ]);
}
