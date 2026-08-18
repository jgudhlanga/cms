<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exports\Maintenance\SponsoredStudentImportTemplateExport;
use App\Importers\Maintenance\SponsoredStudentImporter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentSponsor;
use App\Models\Users\User;
use App\Services\Maintenance\Students\SponsoredStudentImportTemplateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

require_once __DIR__.'/MaintenanceControllerTest.php';
require_once dirname(__DIR__, 2).'/Support/BulkFinaliseTestHelpers.php';

/**
 * @return array{
 *     user: User,
 *     tenantId: int,
 *     institutionDepartment: InstitutionDepartment,
 *     otherInstitutionDepartment: InstitutionDepartment,
 *     calendar: AcademicCalendar,
 *     calendarYear: int,
 * }
 */
function makeSponsoredStudentImportContext(): array
{
    $user = actingAsRootMaintenanceUser();
    $tenantId = (int) $user->tenant_id;
    $calendarYear = (int) now()->format('Y');

    $department = Department::factory()->create(['name' => 'Business Studies']);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $department->id,
        'department_code' => 'BS-'.uniqid(),
        'description' => 'Business studies',
    ]);

    $otherDepartment = Department::factory()->create(['name' => 'Tourism']);
    $otherInstitutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $otherDepartment->id,
        'department_code' => 'TOUR-'.uniqid(),
        'description' => 'Tourism',
    ]);

    foreach (['Semester 1', 'Semester 2'] as $name) {
        Semester::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => null],
        );
    }

    StudentEnrolmentStatus::query()->firstOrCreate(
        ['name' => 'Active'],
        ['description' => 'Test'],
    );

    $calendar = AcademicCalendar::query()->firstOrCreate(
        [
            'calendar_year' => (string) $calendarYear,
            'type' => 'semester',
        ],
        [
            'opening_date' => now()->subDays(30)->toDateString(),
            'closing_date' => now()->addMonths(6)->toDateString(),
        ],
    );

    return [
        'user' => $user,
        'tenantId' => $tenantId,
        'institutionDepartment' => $institutionDepartment,
        'otherInstitutionDepartment' => $otherInstitutionDepartment,
        'calendar' => $calendar,
        'calendarYear' => $calendarYear,
    ];
}

/**
 * @param  array{
 *     classListType?: string|null,
 *     createEnrolment?: bool,
 *     createClassList?: bool,
 *     createAcceptedWorkflow?: bool,
 *     studentNumber?: string|null,
 *     courseName?: string,
 *     levelName?: string,
 *     idNumber?: string|null,
 *     passportNumber?: string|null,
 *     idTypeId?: int|null,
 * }  $options
 * @return array{student: Student, application: StudentApplication|null}
 */
function createSponsoredStudentImportStudent(
    array $context,
    string $idNumber,
    ?string $studentNumber = null,
    ?int $institutionDepartmentId = null,
    array $options = [],
): array {
    $title = Title::query()->create(['name' => 'Mr '.uniqid()]);
    $gender = Gender::query()->create(['title' => 'Gender '.uniqid()]);
    $marital = MaritalStatus::query()->create(['title' => 'Single '.uniqid()]);
    $idType = IdType::query()->create(['name' => 'National ID '.uniqid()]);

    $studentUser = User::factory()->create([
        'tenant_id' => $context['tenantId'],
        'first_name' => 'Sponsored',
        'last_name' => 'Student',
    ]);

    $student = Student::query()->create([
        'tenant_id' => $context['tenantId'],
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'id_type_id' => $options['idTypeId'] ?? $idType->id,
        'id_number' => array_key_exists('idNumber', $options) ? $options['idNumber'] : $idNumber,
        'passport_number' => $options['passportNumber'] ?? null,
        'student_number' => array_key_exists('studentNumber', $options) ? $options['studentNumber'] : $studentNumber,
        'date_of_birth' => '2001-01-01',
    ]);

    if ($institutionDepartmentId === null) {
        return ['student' => $student, 'application' => null];
    }

    $course = Course::factory()->create([
        'name' => $options['courseName'] ?? ('Course '.uniqid()),
    ]);
    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $context['tenantId'],
        'institution_department_id' => $institutionDepartmentId,
        'course_id' => $course->id,
    ]);
    $level = Level::factory()->create([
        'name' => $options['levelName'] ?? ('Level '.uniqid()),
        'calendar_type' => 'semester',
    ]);
    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $context['tenantId'],
        'institution_department_id' => $institutionDepartmentId,
        'level_id' => $level->id,
    ]);
    $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time '.uniqid()]);
    $intakePeriod = IntakePeriod::query()->create([
        'tenant_id' => $context['tenantId'],
        'name' => 'Intake '.$student->id,
        'calendar_year' => (string) $context['calendarYear'],
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]);

    $studentApplication = StudentApplication::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $student->id,
        'institution_department_id' => $institutionDepartmentId,
        'department_level_id' => $departmentLevel->id,
        'department_course_id' => $departmentCourse->id,
        'intake_period_id' => $intakePeriod->id,
        'mode_of_study_id' => $modeOfStudy->id,
        'application_tracking_number' => 'APP-'.strtoupper(uniqid()),
    ]);

    if ($options['createAcceptedWorkflow'] ?? true) {
        $acceptedStep = resolveWorkflowStep(WorkflowStepEnum::ACCEPTED);
        $studentApplication->update([
            'workflow_step_id' => $acceptedStep->id,
        ]);
        resolveWorkflowStep(WorkflowStepEnum::ENROLLED);
    }

    if ($options['createClassList'] ?? true) {
        ClassList::query()->create([
            'tenant_id' => $context['tenantId'],
            'student_application_id' => $studentApplication->id,
            'type' => $options['classListType'] ?? ClassListTypeEnum::VERIFIED->value,
            'attributes' => [],
        ]);
    }

    if ($options['createEnrolment'] ?? false) {
        $semester = Semester::query()->firstOrCreate(
            ['slug' => 'semester-1'],
            ['name' => 'Semester 1', 'description' => null],
        );
        $enrolmentStatus = StudentEnrolmentStatus::query()->firstOrCreate(
            ['name' => 'Active'],
            ['description' => 'Test'],
        );

        StudentEnrolment::query()->create([
            'student_id' => $student->id,
            'student_application_id' => $studentApplication->id,
            'institution_department_id' => $institutionDepartmentId,
            'department_level_id' => $departmentLevel->id,
            'department_course_id' => $departmentCourse->id,
            'semester_id' => $semester->id,
            'academic_calendar_id' => $context['calendar']->id,
            'mode_of_study_id' => $modeOfStudy->id,
            'student_enrolment_status_id' => $enrolmentStatus->id,
        ]);
    }

    return ['student' => $student->fresh(), 'application' => $studentApplication->fresh()];
}

/**
 * @param  list<list<string|null>>  $rows
 */
function storeSponsoredStudentImportFile(array $rows, ?array $headers = null): UploadedFile
{
    $templateService = app(SponsoredStudentImportTemplateService::class);
    $data = $templateService->assemble();
    $data['rows'] = $rows;

    if ($headers !== null) {
        $relativePath = 'test-sponsored-students-import-'.uniqid().'.csv';
        $fullPath = storage_path('app/'.$relativePath);
        $handle = fopen($fullPath, 'w');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return new UploadedFile($fullPath, 'sponsored-students-import.csv', 'text/csv', null, true);
    }

    $relativePath = 'test-sponsored-students-import-'.uniqid().'.xlsx';
    Excel::store(new SponsoredStudentImportTemplateExport($data), $relativePath, 'local');

    return new UploadedFile(storage_path('app/'.$relativePath), 'sponsored-students-import.xlsx', null, null, true);
}

it('redirects guests from sponsored students page', function (): void {
    $this->get(route('maintenance.sponsored-students'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from sponsored students page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.sponsored-students'))
        ->assertForbidden();
});

it('renders sponsored students page for root users', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.sponsored-students'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/SponsoredStudentsManager')
            ->has('calendarYear'));
});

it('redirects guests from sponsored students import template endpoint', function (): void {
    $this->get(route('maintenance.sponsored-students.template'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from sponsored students import template endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.sponsored-students.template'))
        ->assertForbidden();
});

it('downloads sponsored students import template for root users', function (): void {
    actingAsRootMaintenanceUser();

    $response = $this->get(route('maintenance.sponsored-students.template'));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('sponsored-students-import-template');
});

it('redirects guests from sponsored students import preview endpoint', function (): void {
    $this->post(route('maintenance.sponsored-students.preview'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from sponsored students import preview endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.sponsored-students.preview'))
        ->assertForbidden();
});

it('previews sponsored student import rows matched by student number', function (): void {
    $context = makeSponsoredStudentImportContext();

    $created = createSponsoredStudentImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
        [
            'courseName' => 'Business Studies',
            'levelName' => 'ND1',
        ],
    );

    $file = storeSponsoredStudentImportFile([
        ['26HT11013833HP', 'Ministry of Higher Education'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('summary.found', 1)
        ->assertJsonPath('summary.notFound', 0)
        ->assertJsonPath('summary.invalid', 0)
        ->assertJsonPath('summary.selectable', 1)
        ->assertJsonPath('rows.0.status', 'found')
        ->assertJsonPath('rows.0.sponsor', 'Ministry of Higher Education')
        ->assertJsonPath('rows.0.departmentCode', $context['institutionDepartment']->department_code)
        ->assertJsonPath('rows.0.level', 'ND1')
        ->assertJsonPath('rows.0.course', 'Business Studies')
        ->assertJsonPath('rows.0.classListStatus', ClassListTypeEnum::VERIFIED->value)
        ->assertJsonPath('rows.0.studentApplicationId', $created['application']->id)
        ->assertJsonPath('rows.0.idNumberValid', true)
        ->assertJsonPath('rows.0.isAlreadySponsored', false)
        ->assertJsonPath('rows.0.action', 'create')
        ->assertJsonPath('rows.0.isSelectable', true)
        ->assertJsonPath('rows.0.matchedBy', 'student_number')
        ->assertJsonPath('rows.0.identityNumber', '63-1234567N63')
        ->assertJsonPath('rows.0.studentNumber', '26HT11013833HP');
});

it('does not match sponsored student import rows by national id in the student number column', function (): void {
    $context = makeSponsoredStudentImportContext();

    createSponsoredStudentImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
    );

    $file = storeSponsoredStudentImportFile([
        ['63-1234567N63', 'Ministry of Higher Education'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.notFound', 1)
        ->assertJsonPath('rows.0.status', 'not_found')
        ->assertJsonPath('rows.0.isSelectable', false);
});

it('finds sponsored student import rows regardless of department', function (): void {
    $context = makeSponsoredStudentImportContext();

    $created = createSponsoredStudentImportStudent(
        $context,
        '63-2483871S27',
        '26HT11012832HP',
        (int) $context['otherInstitutionDepartment']->id,
    );

    $file = storeSponsoredStudentImportFile([
        ['26HT11012832HP', 'CFAO'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 1)
        ->assertJsonPath('summary.notFound', 0)
        ->assertJsonPath('rows.0.status', 'found')
        ->assertJsonPath('rows.0.isSelectable', true)
        ->assertJsonPath('rows.0.studentApplicationId', $created['application']->id)
        ->assertJsonPath('rows.0.departmentCode', $context['otherInstitutionDepartment']->department_code);
});

it('marks sponsored student import rows as not found when no student matches', function (): void {
    $context = makeSponsoredStudentImportContext();

    $file = storeSponsoredStudentImportFile([
        ['UNKNOWN123', 'RASM'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.notFound', 1)
        ->assertJsonPath('rows.0.status', 'not_found');
});

it('marks sponsored student import rows as invalid when student number is missing', function (): void {
    $context = makeSponsoredStudentImportContext();

    $file = storeSponsoredStudentImportFile([
        [null, 'RASM'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.invalid', 1)
        ->assertJsonPath('rows.0.status', 'invalid');
});

it('keeps already sponsored rows selectable and flags invalid id and failed class list', function (): void {
    $context = makeSponsoredStudentImportContext();

    $invalid = createSponsoredStudentImportStudent(
        $context,
        '63-12345',
        '26HT11010001HP',
        (int) $context['institutionDepartment']->id,
    );

    $already = createSponsoredStudentImportStudent(
        $context,
        '63-2483871S27',
        '26HT11010002HP',
        (int) $context['institutionDepartment']->id,
    );

    StudentSponsor::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $already['student']->id,
        'calendar_year' => $context['calendarYear'],
        'sponsor' => 'Existing Sponsor',
    ]);

    $failed = createSponsoredStudentImportStudent(
        $context,
        '63-2478239W83',
        '26HT11010003HP',
        (int) $context['institutionDepartment']->id,
        ['classListType' => ClassListTypeEnum::FAILED->value],
    );

    $file = storeSponsoredStudentImportFile([
        ['26HT11010001HP', 'Co A'],
        ['26HT11010002HP', 'Co B'],
        ['26HT11010003HP', 'Co D'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 3)
        ->assertJsonPath('summary.invalidId', 1)
        ->assertJsonPath('summary.alreadySponsored', 1)
        ->assertJsonPath('summary.selectable', 1)
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.idNumberValid', false)
        ->assertJsonPath('rows.0.studentApplicationId', $invalid['application']->id)
        ->assertJsonPath('rows.1.isAlreadySponsored', true)
        ->assertJsonPath('rows.1.action', 'update')
        ->assertJsonPath('rows.1.existingSponsor', 'Existing Sponsor')
        ->assertJsonPath('rows.1.isSelectable', true)
        ->assertJsonPath('rows.2.classListStatus', ClassListTypeEnum::FAILED->value)
        ->assertJsonPath('rows.2.isSelectable', false)
        ->assertJsonPath('rows.2.studentApplicationId', $failed['application']->id)
        ->assertJsonPath('rows.0.skipReasons.0', 'invalid id');
});

it('does not skip passport-only students with no zimbabwean id', function (): void {
    $context = makeSponsoredStudentImportContext();

    $passportType = IdType::query()->firstOrCreate(
        ['name' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->value],
        ['description' => IdTypeEnum::FOREIGN_PASSPORT_NUMBER->description(), 'is_default' => false],
    );

    $created = createSponsoredStudentImportStudent(
        $context,
        '',
        '26HT11019999HP',
        (int) $context['institutionDepartment']->id,
        [
            'idNumber' => null,
            'passportNumber' => 'AB1234567',
            'idTypeId' => $passportType->id,
        ],
    );

    $file = storeSponsoredStudentImportFile([
        ['26HT11019999HP', 'UNESCO'],
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('rows.0.isSelectable', true)
        ->assertJsonPath('rows.0.idNumberValid', true)
        ->assertJsonPath('rows.0.identityNumber', 'AB1234567')
        ->assertJsonPath('rows.0.studentApplicationId', $created['application']->id);
});

it('parses sponsor name header aliases', function (): void {
    $context = makeSponsoredStudentImportContext();

    createSponsoredStudentImportStudent(
        $context,
        '50-181796E50',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
    );

    $file = storeSponsoredStudentImportFile(
        [
            ['26HT11013833HP', 'RASM'],
        ],
        ['STUDENT NUMBER', 'SPONSOR NAME'],
    );

    $response = $this->postJson(route('maintenance.sponsored-students.preview'), [
        'file' => $file,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 1)
        ->assertJsonPath('rows.0.studentNumber', '26HT11013833HP')
        ->assertJsonPath('rows.0.sponsor', 'RASM');
});

it('exposes canonical sponsored student import columns on the importer', function (): void {
    expect(SponsoredStudentImporter::COLUMNS)->toBe([
        'Student Number',
        'Sponsor',
    ]);
});

it('redirects guests from sponsored students import process endpoint', function (): void {
    $this->post(route('maintenance.sponsored-students.process'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from sponsored students import process endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.sponsored-students.process'))
        ->assertForbidden();
});

it('moves verified students to final class and creates sponsor records', function (): void {
    $context = makeSponsoredStudentImportContext();

    $created = createSponsoredStudentImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
    );

    $response = $this->postJson(route('maintenance.sponsored-students.process'), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 2,
                'studentApplicationId' => $created['application']->id,
                'sponsor' => 'Ministry of Higher Education',
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.requested', 1)
        ->assertJsonPath('summary.moved', 1)
        ->assertJsonPath('summary.skipped', 0)
        ->assertJsonPath('rows.0.status', 'moved');

    $classList = ClassList::query()
        ->where('student_application_id', $created['application']->id)
        ->first();

    $enrolment = StudentEnrolment::query()
        ->where('student_application_id', $created['application']->id)
        ->first();

    $sponsor = StudentSponsor::query()
        ->where('student_id', $created['student']->id)
        ->where('calendar_year', $context['calendarYear'])
        ->first();

    $application = $created['application']->fresh();
    $enrolledStep = resolveWorkflowStep(WorkflowStepEnum::ENROLLED);

    expect($classList)->not->toBeNull()
        ->and($classList->type)->toBe(ClassListTypeEnum::FINAL)
        ->and($enrolment)->not->toBeNull()
        ->and($sponsor)->not->toBeNull()
        ->and($sponsor->sponsor)->toBe('Ministry of Higher Education')
        ->and($application->workflow_step_id)->toBe($enrolledStep->id);
});

it('updates an existing sponsor for the same student and calendar year without duplicating', function (): void {
    $context = makeSponsoredStudentImportContext();

    $created = createSponsoredStudentImportStudent(
        $context,
        '63-2483871S27',
        '26HT11012832HP',
        (int) $context['institutionDepartment']->id,
        [
            'classListType' => ClassListTypeEnum::FINAL->value,
            'createEnrolment' => true,
        ],
    );

    StudentSponsor::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $created['student']->id,
        'calendar_year' => $context['calendarYear'],
        'sponsor' => 'Old Sponsor',
    ]);

    $response = $this->postJson(route('maintenance.sponsored-students.process'), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 3,
                'studentApplicationId' => $created['application']->id,
                'sponsor' => 'New Sponsor',
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.moved', 1)
        ->assertJsonPath('rows.0.status', 'moved');

    $sponsors = StudentSponsor::query()
        ->where('student_id', $created['student']->id)
        ->where('calendar_year', $context['calendarYear'])
        ->get();

    expect($sponsors)->toHaveCount(1)
        ->and($sponsors->first()->sponsor)->toBe('New Sponsor');
});

it('skips invalid id rows during process', function (): void {
    $context = makeSponsoredStudentImportContext();

    $invalid = createSponsoredStudentImportStudent(
        $context,
        'bad-id',
        '26HT11010002HP',
        (int) $context['institutionDepartment']->id,
    );

    $response = $this->postJson(route('maintenance.sponsored-students.process'), [
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 3,
                'studentApplicationId' => $invalid['application']->id,
                'sponsor' => 'Y',
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.requested', 1)
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 1)
        ->assertJsonPath('rows.0.status', 'skipped');

    expect(
        StudentSponsor::query()
            ->where('student_id', $invalid['student']->id)
            ->exists()
    )->toBeFalse();
});

it('includes the current-year sponsor on the student profile', function (): void {
    $context = makeSponsoredStudentImportContext();

    $created = createSponsoredStudentImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
        ['createEnrolment' => true],
    );

    StudentSponsor::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $created['student']->id,
        'calendar_year' => $context['calendarYear'],
        'sponsor' => 'Ministry of Higher Education',
    ]);

    $admin = User::factory()->create(['tenant_id' => $context['tenantId']]);
    $admin->givePermissionTo(['view:students', 'viewAny:students']);

    $this->actingAs($admin)
        ->get(route('students.show', $created['student']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/Show')
            ->where('student.attributes.isSponsoredThisYear', true)
            ->where('student.attributes.sponsor', 'Ministry of Higher Education'));
});
