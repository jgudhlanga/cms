<?php

use App\Exports\Maintenance\StaffImportTemplateExport;
use App\Importers\Maintenance\StaffImporter;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Maintenance\StaffImportLog;
use App\Models\Shared\Address;
use App\Models\Shared\Contact;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use App\Services\Maintenance\StaffImportTemplateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

require_once __DIR__.'/MaintenanceControllerTest.php';

/**
 * @return array{
 *     user: User,
 *     tenantId: int,
 *     departmentName: string,
 *     title: Title,
 *     gender: Gender,
 *     maritalStatus: MaritalStatus,
 *     employmentType: EmploymentType,
 *     institutionDepartment: InstitutionDepartment,
 * }
 */
function makeStaffImportContext(): array
{
    $user = actingAsRootMaintenanceUser();
    $tenantId = (int) $user->tenant_id;

    $title = Title::factory()->create(['name' => 'Mr']);
    $gender = Gender::factory()->create(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->create(['title' => 'Single']);
    $employmentType = EmploymentType::factory()->create(['name' => 'Permanent']);

    $department = Department::factory()->create(['name' => 'Engineering Import']);
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $department->id,
        'department_code' => 'ENG-IMP-'.uniqid(),
        'description' => 'Engineering import test department',
    ]);

    return [
        'user' => $user,
        'tenantId' => $tenantId,
        'departmentName' => 'Engineering Import',
        'title' => $title,
        'gender' => $gender,
        'maritalStatus' => $maritalStatus,
        'employmentType' => $employmentType,
        'institutionDepartment' => $institutionDepartment,
    ];
}

/**
 * @param  array<string, string|null>  $overrides
 * @return list<string|null>
 */
function staffImportRowValues(array $overrides = []): array
{
    $defaults = [
        'EMPLOYEE_NUMBER' => 'EC-IMPORT-001',
        'TITLE' => 'Mr',
        'FIRST_NAME' => 'John',
        'MIDDLE_NAME' => null,
        'LAST_NAME' => 'Doe',
        'GENDER' => 'Male',
        'MARITAL_STATUS' => 'Single',
        'EMPLOYMENT_TYPE' => 'Permanent',
        'DATE_OF_BIRTH' => '1990-01-15',
        'EMAIL' => 'john.doe.import@example.test',
        'PHONE_NUMBER' => '+263770000001',
        'DEPARTMENT' => 'Engineering Import',
        'ROLES' => null,
        'ID_NUMBER' => 'ID-001',
        'PASSPORT_NUMBER' => null,
        'ALT_PHONE_NUMBER' => '+263770000002',
        'ALT_EMAIL_ADDRESS' => 'john.alt@example.test',
        'ADDRESS_1' => '123 Main Street',
        'ADDRESS_2' => 'Harare',
        'ADDRESS_3' => null,
        'ADDRESS_4' => null,
    ];

    $merged = array_merge($defaults, $overrides);

    return array_map(
        static fn (string $column): ?string => $merged[$column] ?? null,
        StaffImporter::COLUMNS,
    );
}

/**
 * @param  list<list<string|null>>  $rows
 */
function storeStaffImportFile(array $rows, int $tenantId): UploadedFile
{
    $templateService = app(StaffImportTemplateService::class);
    $data = $templateService->assemble($tenantId);
    $data['rows'] = $rows;

    $relativePath = 'test-staff-import-'.uniqid().'.xlsx';
    Excel::store(new StaffImportTemplateExport($data), $relativePath, 'local');

    return new UploadedFile(storage_path('app/'.$relativePath), 'staff-import.xlsx', null, null, true);
}

function staffImportPreviewAndProcess(UploadedFile $file): string
{
    $previewResponse = test()->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful();

    $previewToken = $previewResponse->json('previewToken');
    expect($previewToken)->not->toBeEmpty();

    test()->post(route('maintenance.staff-import.process'), [
        'preview_token' => $previewToken,
    ])->assertRedirect(route('maintenance.index'));

    return $previewToken;
}

it('redirects guests from staff import template endpoint', function (): void {
    $this->get(route('maintenance.staff-import.template'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from staff import template endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('maintenance.staff-import.template'))
        ->assertForbidden();
});

it('allows root users to download staff import template', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.staff-import.template'))
        ->assertSuccessful()
        ->assertDownload();
});

it('redirects guests from staff import preview endpoint', function (): void {
    $this->post(route('maintenance.staff-import.preview'))
        ->assertRedirect('/login');
});

it('forbids users without root manage from staff import preview endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.staff-import.preview'))
        ->assertForbidden();
});

it('previews and imports staff from template file', function (): void {
    $context = makeStaffImportContext();
    $file = storeStaffImportFile([staffImportRowValues()], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('summary.creates', 1)
        ->assertJsonPath('summary.failed', 0);

    staffImportPreviewAndProcess($file);

    $staff = Staff::query()
        ->where('employee_number', 'EC-IMPORT-001')
        ->where('tenant_id', $context['tenantId'])
        ->first();

    expect($staff)->not->toBeNull()
        ->and($staff->user->email)->toBe('john.doe.import@example.test')
        ->and($staff->institutionDepartments()->where('institution_departments.id', $context['institutionDepartment']->id)->exists())->toBeTrue()
        ->and(Contact::query()->where('contactable_type', Staff::class)->where('contactable_id', $staff->id)->exists())->toBeTrue()
        ->and(Address::query()->where('addressable_type', Staff::class)->where('addressable_id', $staff->id)->exists())->toBeTrue()
        ->and(StaffImportLog::query()->count())->toBe(1);
});

it('upserts existing staff on re-import by employee number', function (): void {
    $context = makeStaffImportContext();
    $file = storeStaffImportFile([staffImportRowValues()], $context['tenantId']);

    staffImportPreviewAndProcess($file);

    $updatedFile = storeStaffImportFile([
        staffImportRowValues([
            'FIRST_NAME' => 'Jonathan',
            'EMAIL' => 'john.doe.import@example.test',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $updatedFile,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('summary.updates', 1)
        ->assertJsonPath('summary.creates', 0);

    staffImportPreviewAndProcess($updatedFile);

    expect(Staff::query()->where('employee_number', 'EC-IMPORT-001')->count())->toBe(1)
        ->and(Staff::query()->where('employee_number', 'EC-IMPORT-001')->first()?->user->first_name)->toBe('Jonathan');
});

it('fails preview row for unknown department', function (): void {
    $context = makeStaffImportContext();
    $file = storeStaffImportFile([
        staffImportRowValues(['DEPARTMENT' => 'Unknown Department']),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('summary.failed', 1)
        ->assertJsonPath('summary.creates', 0);
});

it('rejects expired preview token on process', function (): void {
    $context = makeStaffImportContext();
    test()->actingAs($context['user']);

    $this->post(route('maintenance.staff-import.process'), [
        'preview_token' => 'invalid-token',
    ])->assertSessionHasErrors('preview_token');
});

it('rejects preview token for a different user', function (): void {
    $context = makeStaffImportContext();
    $file = storeStaffImportFile([staffImportRowValues()], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewToken = $previewResponse->json('previewToken');

    $otherUser = User::factory()->create(['tenant_id' => $context['tenantId']]);
    $otherUser->givePermissionTo('root:manage');

    $this->actingAs($otherUser)
        ->post(route('maintenance.staff-import.process'), [
            'preview_token' => $previewToken,
        ])
        ->assertSessionHasErrors('preview_token');

    Cache::forget('staff-import-preview:'.$previewToken);
});

it('fails preview row for duplicate email on create', function (): void {
    $context = makeStaffImportContext();
    User::factory()->create([
        'tenant_id' => $context['tenantId'],
        'email' => 'existing.user@example.test',
    ]);

    $file = storeStaffImportFile([
        staffImportRowValues([
            'EMPLOYEE_NUMBER' => 'EC-IMPORT-002',
            'EMAIL' => 'existing.user@example.test',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful();

    $previewToken = $previewResponse->json('previewToken');

    $this->post(route('maintenance.staff-import.process'), [
        'preview_token' => $previewToken,
    ])->assertRedirect(route('maintenance.index'));

    expect(Staff::query()->where('employee_number', 'EC-IMPORT-002')->exists())->toBeFalse();
});
