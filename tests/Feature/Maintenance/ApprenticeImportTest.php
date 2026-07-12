<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exports\Maintenance\ApprenticeImportTemplateExport;
use App\Importers\Maintenance\ApprenticeImporter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
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
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Users\User;
use App\Services\Maintenance\Students\ApprenticeImportTemplateService;
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
function makeApprenticeImportContext(): array
{
    $user = actingAsRootMaintenanceUser();
    $tenantId = (int) $user->tenant_id;
    $calendarYear = (int) now()->format('Y');

    $department = Department::factory()->create(['name' => 'Motor Mechanics']);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $department->id,
        'department_code' => 'MM-'.uniqid(),
        'description' => 'Motor mechanics apprentices',
    ]);

    $otherDepartment = Department::factory()->create(['name' => 'Tourism']);
    $otherInstitutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $otherDepartment->id,
        'department_code' => 'TOUR-'.uniqid(),
        'description' => 'Tourism apprentices',
    ]);

    foreach (['Semester 1', 'Semester 2'] as $name) {
        AcademicYearOption::query()->firstOrCreate(
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
 * }  $options
 * @return array{student: Student, application: StudentApplication|null}
 */
function createApprenticeImportStudent(
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
        'first_name' => 'Apprentice',
        'last_name' => 'Student',
    ]);

    $student = Student::query()->create([
        'tenant_id' => $context['tenantId'],
        'user_id' => $studentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'id_type_id' => $idType->id,
        'id_number' => $idNumber,
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
        $acceptedStep = resolveDepartmentApplicationStep($studentApplication, WorkflowStepEnum::ACCEPTED);
        $studentApplication->update([
            'department_application_step_id' => $acceptedStep->id,
        ]);
        resolveDepartmentApplicationStep($studentApplication, WorkflowStepEnum::ENROLLED);
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
        $academicYearOption = AcademicYearOption::query()->firstOrCreate(
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
            'academic_year_option_id' => $academicYearOption->id,
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
function storeApprenticeImportFile(array $rows, ?array $headers = null): UploadedFile
{
    $templateService = app(ApprenticeImportTemplateService::class);
    $data = $templateService->assemble();
    $data['rows'] = $rows;

    if ($headers !== null) {
        $relativePath = 'test-apprentice-import-'.uniqid().'.csv';
        $fullPath = storage_path('app/'.$relativePath);
        $handle = fopen($fullPath, 'w');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return new UploadedFile($fullPath, 'apprentice-import.csv', 'text/csv', null, true);
    }

    $relativePath = 'test-apprentice-import-'.uniqid().'.xlsx';
    Excel::store(new ApprenticeImportTemplateExport($data), $relativePath, 'local');

    return new UploadedFile(storage_path('app/'.$relativePath), 'apprentice-import.xlsx', null, null, true);
}

it('redirects guests from apprentice management page', function (): void {
    $this->get(route('maintenance.apprentice-management'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from apprentice management page', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.apprentice-management'))
        ->assertForbidden();
});

it('renders apprentice management page for root users', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.apprentice-management'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('maintenance/ApprenticeManager')
            ->has('calendarYear'));
});

it('redirects guests from apprentice import template endpoint', function (): void {
    $this->get(route('maintenance.apprentice-management.template'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from apprentice import template endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.apprentice-management.template'))
        ->assertForbidden();
});

it('downloads apprentice import template for root users', function (): void {
    actingAsRootMaintenanceUser();

    $response = $this->get(route('maintenance.apprentice-management.template'));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('apprentice-import-template');
});

it('redirects guests from apprentice import preview endpoint', function (): void {
    $this->post(route('maintenance.apprentice-management.preview'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from apprentice import preview endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.apprentice-management.preview'))
        ->assertForbidden();
});

it('previews apprentice import rows with found students in the selected department', function (): void {
    $context = makeApprenticeImportContext();

    $created = createApprenticeImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
        [
            'courseName' => 'Motor Vehicle Mechanics',
            'levelName' => 'ND1',
        ],
    );

    $file = storeApprenticeImportFile([
        ['63-1234567N63', '26HT11013833HP', '2500178J', 'RASM'],
    ]);

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.total', 1)
        ->assertJsonPath('summary.found', 1)
        ->assertJsonPath('summary.notFound', 0)
        ->assertJsonPath('summary.invalid', 0)
        ->assertJsonPath('summary.selectable', 1)
        ->assertJsonPath('rows.0.status', 'found')
        ->assertJsonPath('rows.0.apprenticeNumber', '2500178J')
        ->assertJsonPath('rows.0.employer', 'RASM')
        ->assertJsonPath('rows.0.departmentCode', $context['institutionDepartment']->department_code)
        ->assertJsonPath('rows.0.level', 'ND1')
        ->assertJsonPath('rows.0.course', 'Motor Vehicle Mechanics')
        ->assertJsonPath('rows.0.classListStatus', ClassListTypeEnum::VERIFIED->value)
        ->assertJsonPath('rows.0.studentApplicationId', $created['application']->id)
        ->assertJsonPath('rows.0.idNumberValid', true)
        ->assertJsonPath('rows.0.isAlreadyApprentice', false)
        ->assertJsonPath('rows.0.isSelectable', true)
        ->assertJsonPath('rows.0.matchedBy', 'id_number')
        ->assertJsonPath('rows.0.storedIdNumber', '63-1234567N63');

    expect($response->json('rows.0'))->not->toHaveKey('faultyStudentIdsUrl');
});

it('sets matchedBy to student_number when only the student number resolves', function (): void {
    $context = makeApprenticeImportContext();

    createApprenticeImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
    );

    $file = storeApprenticeImportFile([
        ['99-9999999Z99', '26HT11013833HP', '2500178J', 'RASM'],
    ]);

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('rows.0.status', 'found')
        ->assertJsonPath('rows.0.matchedBy', 'student_number');
});

it('marks apprentice import rows as not found when the student is in another department', function (): void {
    $context = makeApprenticeImportContext();

    createApprenticeImportStudent(
        $context,
        '63-2483871S27',
        '26HT11012832HP',
        (int) $context['otherInstitutionDepartment']->id,
    );

    $file = storeApprenticeImportFile([
        ['63-2483871S27', null, null, 'CFAO'],
    ]);

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 0)
        ->assertJsonPath('summary.notFound', 1)
        ->assertJsonPath('rows.0.status', 'not_found')
        ->assertJsonPath('rows.0.isSelectable', false);
});

it('marks apprentice import rows as not found when no student matches', function (): void {
    $context = makeApprenticeImportContext();

    $file = storeApprenticeImportFile([
        ['99-9999999Z99', 'UNKNOWN123', null, 'RASM'],
    ]);

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.notFound', 1)
        ->assertJsonPath('rows.0.status', 'not_found');
});

it('marks apprentice import rows as invalid when identifiers are missing', function (): void {
    $context = makeApprenticeImportContext();

    $file = storeApprenticeImportFile([
        [null, null, '2500178J', 'RASM'],
    ]);

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.invalid', 1)
        ->assertJsonPath('rows.0.status', 'invalid');
});

it('flags invalid id numbers and already apprentice rows as not selectable', function (): void {
    $context = makeApprenticeImportContext();

    $invalid = createApprenticeImportStudent(
        $context,
        '63-12345',
        '26HT11010001HP',
        (int) $context['institutionDepartment']->id,
    );

    $already = createApprenticeImportStudent(
        $context,
        '63-2483871S27',
        '26HT11010002HP',
        (int) $context['institutionDepartment']->id,
    );

    StudentApprentice::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $already['student']->id,
        'calendar_year' => $context['calendarYear'],
        'employer' => 'Existing Co',
        'apprentice_number' => 'OLD-1',
    ]);

    $missingNumber = createApprenticeImportStudent(
        $context,
        '50-181796E50',
        null,
        (int) $context['institutionDepartment']->id,
        ['studentNumber' => null],
    );

    $failed = createApprenticeImportStudent(
        $context,
        '63-2478239W83',
        '26HT11010003HP',
        (int) $context['institutionDepartment']->id,
        ['classListType' => ClassListTypeEnum::FAILED->value],
    );

    $file = storeApprenticeImportFile([
        ['63-12345', '26HT11010001HP', 'A1', 'Co A'],
        ['63-2483871S27', '26HT11010002HP', 'A2', 'Co B'],
        ['50-181796E50', null, 'A3', 'Co C'],
        ['63-2478239W83', '26HT11010003HP', 'A4', 'Co D'],
    ]);

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 4)
        ->assertJsonPath('summary.invalidId', 1)
        ->assertJsonPath('summary.alreadyApprentice', 1)
        ->assertJsonPath('summary.selectable', 0)
        ->assertJsonPath('rows.0.isSelectable', false)
        ->assertJsonPath('rows.0.idNumberValid', false)
        ->assertJsonPath('rows.0.studentApplicationId', $invalid['application']->id)
        ->assertJsonPath('rows.1.isAlreadyApprentice', true)
        ->assertJsonPath('rows.1.isSelectable', false)
        ->assertJsonPath('rows.2.isSelectable', false)
        ->assertJsonPath('rows.2.studentApplicationId', $missingNumber['application']->id)
        ->assertJsonPath('rows.3.classListStatus', ClassListTypeEnum::FAILED->value)
        ->assertJsonPath('rows.3.isSelectable', false)
        ->assertJsonPath('rows.3.studentApplicationId', $failed['application']->id)
        ->assertJsonPath('rows.0.storedIdNumber', '63-12345')
        ->assertJsonPath('rows.0.skipReasons.0', 'invalid id');

    expect($response->json('rows.0'))->not->toHaveKey('faultyStudentIdsUrl');
});

it('redirects guests from apprentice import refresh row endpoint', function (): void {
    $this->post(route('maintenance.apprentice-management.refresh-row'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from apprentice import refresh row endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.apprentice-management.refresh-row'))
        ->assertForbidden();
});

it('refreshes an apprentice import row after the student id number is corrected', function (): void {
    $context = makeApprenticeImportContext();

    $created = createApprenticeImportStudent(
        $context,
        '63-12345',
        '26HT11010001HP',
        (int) $context['institutionDepartment']->id,
    );

    $created['student']->update(['id_number' => '63-1234567N63']);

    $response = $this->postJson(route('maintenance.apprentice-management.refresh-row'), [
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
        'rowNumber' => 1,
        'idNumber' => '63-12345',
        'studentNumber' => '26HT11010001HP',
        'apprenticeNumber' => 'A1',
        'employer' => 'Co A',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('row.idNumberValid', true)
        ->assertJsonPath('row.isSelectable', true)
        ->assertJsonPath('row.matchedBy', 'student_number')
        ->assertJsonPath('row.storedIdNumber', '63-1234567N63')
        ->assertJsonPath('row.studentApplicationId', $created['application']->id);
});

it('returns merge preview json for faulty student id merge modal', function (): void {
    $rootUser = actingAsRootMaintenanceUser();

    $target = Student::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => User::factory()->create(['tenant_id' => $rootUser->tenant_id])->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'id_type_id' => IdType::query()->firstOrCreate(['name' => 'National ID'])->id,
        'id_number' => '63-1234567N63',
        'student_number' => 'MERGE-TGT-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);

    $faulty = Student::query()->create([
        'tenant_id' => $rootUser->tenant_id,
        'user_id' => User::factory()->create(['tenant_id' => $rootUser->tenant_id])->id,
        'title_id' => Title::query()->firstOrCreate(['name' => 'Mr'])->id,
        'gender_id' => Gender::query()->firstOrCreate(['title' => 'Male'])->id,
        'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
        'id_type_id' => IdType::query()->firstOrCreate(['name' => 'National ID'])->id,
        'id_number' => 'invalid-id',
        'student_number' => 'MERGE-FAULTY-'.strtoupper(Str::random(4)),
        'date_of_birth' => '2000-01-01',
    ]);

    $this->getJson(route('maintenance.faulty-student-ids.merge-preview', [
        'student' => $faulty->id,
        'target' => $target->id,
        'id_number' => '63-1234567N63',
    ]))
        ->assertSuccessful()
        ->assertJsonPath('data.proposedIdNumber', '63-1234567N63')
        ->assertJsonPath('data.source.studentId', $faulty->id)
        ->assertJsonPath('data.target.studentId', $target->id);
});

it('parses flexible apprentice import headers from tourism style files', function (): void {
    $context = makeApprenticeImportContext();

    createApprenticeImportStudent(
        $context,
        '50-181796E50',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
    );

    $file = storeApprenticeImportFile(
        [
            ['# *', '2500178J', 'Sakarombe Diana Clara', '26HT11013833HP', '50-181796E50', 'RASM'],
        ],
        ['Indicator', 'Number', 'Apprentice', 'STUDENT NUMBER', 'ID NUMBER', 'Employer'],
    );

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 1)
        ->assertJsonPath('rows.0.studentNumber', '26HT11013833HP')
        ->assertJsonPath('rows.0.idNumber', '50-181796E50')
        ->assertJsonPath('rows.0.apprenticeNumber', '2500178J');
});

it('parses flexible apprentice import headers from motor mechanics style files', function (): void {
    $context = makeApprenticeImportContext();

    createApprenticeImportStudent(
        $context,
        '63-2478239W83',
        '26HT11012850HP',
        (int) $context['institutionDepartment']->id,
    );

    $file = storeApprenticeImportFile(
        [
            ['1.', 'MATANYANGE', 'DAVIN', '37837', '63-2478239W83', '789576070', 'CFAO', 'M'],
        ],
        ['No.', 'Surname', 'First name', 'DOB', 'National ID Number', 'Contact Number', 'C0MPANY', 'Gender'],
    );

    $response = $this->postJson(route('maintenance.apprentice-management.preview'), [
        'file' => $file,
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.found', 1)
        ->assertJsonPath('rows.0.idNumber', '63-2478239W83')
        ->assertJsonPath('rows.0.employer', 'CFAO');
});

it('exposes canonical apprentice import columns on the importer', function (): void {
    expect(ApprenticeImporter::COLUMNS)->toBe([
        'ID Number',
        'Student Number',
        'Apprentice Number',
        'Employer',
    ]);
});

it('redirects guests from apprentice import process endpoint', function (): void {
    $this->post(route('maintenance.apprentice-management.process'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from apprentice import process endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.apprentice-management.process'))
        ->assertForbidden();
});

it('moves verified students to final class and creates apprentice records', function (): void {
    $context = makeApprenticeImportContext();

    $created = createApprenticeImportStudent(
        $context,
        '63-1234567N63',
        '26HT11013833HP',
        (int) $context['institutionDepartment']->id,
    );

    $response = $this->postJson(route('maintenance.apprentice-management.process'), [
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 2,
                'studentApplicationId' => $created['application']->id,
                'apprenticeNumber' => '2500178J',
                'employer' => 'RASM',
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

    $apprentice = StudentApprentice::query()
        ->where('student_id', $created['student']->id)
        ->where('calendar_year', $context['calendarYear'])
        ->first();

    $application = $created['application']->fresh();
    $enrolledStep = resolveDepartmentApplicationStep($application, WorkflowStepEnum::ENROLLED);

    expect($classList)->not->toBeNull()
        ->and($classList->type)->toBe(ClassListTypeEnum::FINAL)
        ->and($enrolment)->not->toBeNull()
        ->and($apprentice)->not->toBeNull()
        ->and($apprentice->employer)->toBe('RASM')
        ->and($apprentice->apprentice_number)->toBe('2500178J')
        ->and($application->department_application_step_id)->toBe($enrolledStep->id);
});

it('creates apprentice records for already final students without error', function (): void {
    $context = makeApprenticeImportContext();

    $created = createApprenticeImportStudent(
        $context,
        '63-2483871S27',
        '26HT11012832HP',
        (int) $context['institutionDepartment']->id,
        [
            'classListType' => ClassListTypeEnum::FINAL->value,
            'createEnrolment' => true,
        ],
    );

    $response = $this->postJson(route('maintenance.apprentice-management.process'), [
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 3,
                'studentApplicationId' => $created['application']->id,
                'apprenticeNumber' => 'NEW-99',
                'employer' => 'CFAO',
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.moved', 1)
        ->assertJsonPath('rows.0.status', 'moved');

    $apprentice = StudentApprentice::query()
        ->where('student_id', $created['student']->id)
        ->where('calendar_year', $context['calendarYear'])
        ->first();

    expect($apprentice)->not->toBeNull()
        ->and($apprentice->employer)->toBe('CFAO')
        ->and($apprentice->apprentice_number)->toBe('NEW-99');
});

it('skips already apprentice and invalid rows during process', function (): void {
    $context = makeApprenticeImportContext();

    $already = createApprenticeImportStudent(
        $context,
        '63-1234567N63',
        '26HT11010001HP',
        (int) $context['institutionDepartment']->id,
    );

    StudentApprentice::query()->create([
        'tenant_id' => $context['tenantId'],
        'student_id' => $already['student']->id,
        'calendar_year' => $context['calendarYear'],
        'employer' => 'Old Employer',
        'apprentice_number' => 'OLD',
    ]);

    $invalid = createApprenticeImportStudent(
        $context,
        'bad-id',
        '26HT11010002HP',
        (int) $context['institutionDepartment']->id,
    );

    $response = $this->postJson(route('maintenance.apprentice-management.process'), [
        'institution_department_id' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendarYear'],
        'rows' => [
            [
                'rowNumber' => 2,
                'studentApplicationId' => $already['application']->id,
                'apprenticeNumber' => 'NEW',
                'employer' => 'New Employer',
            ],
            [
                'rowNumber' => 3,
                'studentApplicationId' => $invalid['application']->id,
                'apprenticeNumber' => 'X',
                'employer' => 'Y',
            ],
        ],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.requested', 2)
        ->assertJsonPath('summary.moved', 0)
        ->assertJsonPath('summary.skipped', 2)
        ->assertJsonPath('rows.0.status', 'skipped')
        ->assertJsonPath('rows.1.status', 'skipped');

    $apprentice = StudentApprentice::query()
        ->where('student_id', $already['student']->id)
        ->where('calendar_year', $context['calendarYear'])
        ->first();

    expect($apprentice->employer)->toBe('Old Employer')
        ->and($apprentice->apprentice_number)->toBe('OLD')
        ->and(
            StudentApprentice::query()
                ->where('student_id', $invalid['student']->id)
                ->exists()
        )->toBeFalse();
});
