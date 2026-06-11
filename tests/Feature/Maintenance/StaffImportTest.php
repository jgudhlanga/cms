<?php

use App\Enums\Acl\RoleGroupEnum;
use App\Exports\Maintenance\StaffImportTemplateExport;
use App\Helpers\PermissionHelper;
use App\Importers\Maintenance\StaffImporter;
use App\Models\Acl\Role;
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
use App\Services\Maintenance\Staff\StaffImportTemplateService;
use Database\Seeders\Acl\RoleGroupSeeder;
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

it('normalizes excel cell values for import parsing', function (): void {
    expect(StaffImporter::normalizeCellValue(new DateTimeImmutable('1982-04-24')))->toBe('1982-04-24')
        ->and(StaffImporter::normalizeCellValue(773801735))->toBe('773801735')
        ->and(StaffImporter::normalizeCellValue('  Mr '))->toBe('Mr');
});

it('previews staff with fuzzy lookup values and returns field metadata', function (): void {
    $context = makeStaffImportContext();
    $marriedStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Married']);
    EmploymentType::query()->firstOrCreate(['name' => 'Full time']);

    $file = storeStaffImportFile([
        staffImportRowValues([
            'TITLE' => 'Mr ',
            'GENDER' => 'male',
            'MARITAL_STATUS' => 'married',
            'EMPLOYMENT_TYPE' => 'Full time',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('summary.failed', 0)
        ->assertJsonPath('summary.creates', 1)
        ->assertJsonStructure([
            'lookups' => ['titles', 'genders', 'maritalStatuses', 'employmentTypes', 'departments', 'roles'],
            'rows' => [
                [
                    'fields' => ['title', 'gender', 'maritalStatus', 'employmentType', 'department', 'roles'],
                    'needsReview',
                ],
            ],
        ])
        ->assertJsonPath('rows.0.fields.gender.resolvedId', $context['gender']->id)
        ->assertJsonPath('rows.0.fields.maritalStatus.resolvedId', $marriedStatus->id);
});

it('normalizes DateTimeImmutable cell values during preview', function (): void {
    actingAsRootMaintenanceUser();

    $hospitalityFile = base_path('public/Departmental Staff Data HOSPITALITY.xlsx');

    if (! file_exists($hospitalityFile)) {
        $this->markTestSkipped('Hospitality staff import fixture is not available.');
    }

    $file = new UploadedFile($hospitalityFile, 'Departmental Staff Data HOSPITALITY.xlsx', null, null, true);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonStructure([
            'previewToken',
            'summary',
            'lookups',
            'rows' => [
                [
                    'rowNumber',
                    'fields',
                ],
            ],
        ])
        ->assertJsonPath('summary.total', fn (int $total): bool => $total > 0);
});

it('includes importable role groups in preview lookups and resolves them by name', function (): void {
    $this->seed(RoleGroupSeeder::class);

    $context = makeStaffImportContext();

    $genHandRole = Role::factory()->create([
        'name' => 'Gen Hand',
        'slug' => 'gen-hand',
        'guard_name' => 'web',
        'role_group_id' => PermissionHelper::getGroupId(RoleGroupEnum::ACADEMIC->value),
    ]);

    $registryClerkRole = Role::factory()->create([
        'name' => 'Registry Clerk',
        'slug' => 'registry-clerk',
        'guard_name' => 'web',
        'role_group_id' => PermissionHelper::getGroupId(RoleGroupEnum::ADMINISTRATIVE->value),
    ]);

    $file = storeStaffImportFile([
        staffImportRowValues([
            'ROLES' => 'Registry Clerk',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('rows.0.fields.roles.0.resolvedId', $registryClerkRole->id)
        ->assertJsonPath('rows.0.fields.roles.0.resolvedLabel', 'Registry Clerk');

    $lookupRoles = collect($previewResponse->json('lookups.roles'));
    $lookupRoleIds = $lookupRoles->pluck('value');

    expect($lookupRoleIds)->toContain($genHandRole->id)
        ->and($lookupRoleIds)->toContain($registryClerkRole->id)
        ->and($lookupRoles->firstWhere('value', $genHandRole->id)['roleGroup'])->toBe('Academic')
        ->and($lookupRoles->firstWhere('value', $registryClerkRole->id)['roleGroup'])->toBe('Administrative');
});

it('imports staff with lecturer role slug resolved to role name', function (): void {
    $context = makeStaffImportContext();

    $lecturerRole = Role::factory()->create([
        'name' => 'Lecturer',
        'slug' => 'lecturer',
        'guard_name' => 'web',
        'role_group_id' => PermissionHelper::getGroupId(RoleGroupEnum::ACADEMIC->value),
    ]);

    $file = storeStaffImportFile([
        staffImportRowValues([
            'ROLES' => 'lecturer',
        ]),
    ], $context['tenantId']);

    staffImportPreviewAndProcess($file);

    $staff = Staff::query()
        ->where('employee_number', 'EC-IMPORT-001')
        ->where('tenant_id', $context['tenantId'])
        ->first();

    expect($staff)->not->toBeNull()
        ->and($staff->user->hasRole($lecturerRole->name))->toBeTrue();
});

it('processes import with row corrections overriding lookup values', function (): void {
    $context = makeStaffImportContext();
    $alternateGender = Gender::factory()->create(['title' => 'Female']);

    $file = storeStaffImportFile([
        staffImportRowValues([
            'GENDER' => 'not-a-real-gender',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('summary.failed', 1);

    $previewToken = $previewResponse->json('previewToken');

    $this->post(route('maintenance.staff-import.process'), [
        'preview_token' => $previewToken,
        'row_corrections' => [
            1 => [
                'genderId' => $alternateGender->id,
            ],
        ],
    ])->assertRedirect(route('maintenance.index'));

    $staff = Staff::query()
        ->where('employee_number', 'EC-IMPORT-001')
        ->where('tenant_id', $context['tenantId'])
        ->first();

    expect($staff)->not->toBeNull()
        ->and($staff->gender_id)->toBe($alternateGender->id);
});

it('redirects guests from staff import lookup create endpoint', function (): void {
    $this->post(route('maintenance.staff-import.lookups.create'), [
        'type' => 'title',
        'name' => 'Dr',
    ])->assertRedirect('/login');
});

it('forbids users without root manage from staff import lookup create endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('maintenance.staff-import.lookups.create'), [
            'type' => 'title',
            'name' => 'Dr',
        ])
        ->assertForbidden();
});

it('creates title lookup for staff import preview', function (): void {
    actingAsRootMaintenanceUser();

    $response = $this->postJson(route('maintenance.staff-import.lookups.create'), [
        'type' => 'title',
        'name' => 'Prof',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['value', 'label'])
        ->assertJsonPath('label', 'Prof');

    expect(Title::query()->where('name', 'Prof')->exists())->toBeTrue();
});

it('creates role lookup for staff import preview', function (): void {
    actingAsRootMaintenanceUser();

    $response = $this->postJson(route('maintenance.staff-import.lookups.create'), [
        'type' => 'role',
        'name' => 'Guest Lecturer',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure(['value', 'label'])
        ->assertJsonPath('label', 'Guest Lecturer');

    expect(Role::query()->where('name', 'Guest Lecturer')->exists())->toBeTrue();
});

it('processes import with newly created role correction', function (): void {
    $context = makeStaffImportContext();

    $createResponse = $this->postJson(route('maintenance.staff-import.lookups.create'), [
        'type' => 'role',
        'name' => 'Industry Mentor',
    ]);

    $createResponse->assertSuccessful();
    $roleId = (int) $createResponse->json('value');

    $file = storeStaffImportFile([
        staffImportRowValues([
            'ROLES' => 'industry-mentor',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful();

    $previewToken = $previewResponse->json('previewToken');

    $this->post(route('maintenance.staff-import.process'), [
        'preview_token' => $previewToken,
        'row_corrections' => [
            1 => [
                'roleIds' => [$roleId],
            ],
        ],
    ])->assertRedirect(route('maintenance.index'));

    $staff = Staff::query()
        ->where('employee_number', 'EC-IMPORT-001')
        ->where('tenant_id', $context['tenantId'])
        ->first();

    expect($staff)->not->toBeNull()
        ->and($staff->user->hasRole('Industry Mentor'))->toBeTrue();
});

it('creates department lookup linked to tenant for staff import preview', function (): void {
    $context = makeStaffImportContext();

    $response = $this->postJson(route('maintenance.staff-import.lookups.create'), [
        'type' => 'department',
        'name' => 'Hospitality',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('label', 'Hospitality');

    $institutionDepartment = InstitutionDepartment::query()
        ->where('tenant_id', $context['tenantId'])
        ->whereHas('department', fn ($query) => $query->where('name', 'Hospitality'))
        ->first();

    expect($institutionDepartment)->not->toBeNull()
        ->and($response->json('value'))->toBe($institutionDepartment->id);
});

it('processes import with corrected email address', function (): void {
    $context = makeStaffImportContext();

    $file = storeStaffImportFile([
        staffImportRowValues([
            'EMAIL' => 'not-an-email',
        ]),
    ], $context['tenantId']);

    $previewResponse = $this->post(route('maintenance.staff-import.preview'), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful()
        ->assertJsonPath('summary.failed', 1);

    $previewToken = $previewResponse->json('previewToken');

    $this->post(route('maintenance.staff-import.process'), [
        'preview_token' => $previewToken,
        'row_corrections' => [
            1 => [
                'email' => 'fixed.email@example.test',
            ],
        ],
    ])->assertRedirect(route('maintenance.index'));

    $staff = Staff::query()
        ->where('employee_number', 'EC-IMPORT-001')
        ->where('tenant_id', $context['tenantId'])
        ->first();

    expect($staff)->not->toBeNull()
        ->and($staff->user->email)->toBe('fixed.email@example.test');
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
    ])
        ->assertRedirect(route('maintenance.index'))
        ->assertSessionHas('staffImportResult.failedRows', function (array $failedRows): bool {
            expect($failedRows)->toHaveCount(1)
                ->and($failedRows[0]['rowNumber'])->toBe(1)
                ->and($failedRows[0]['employeeNumber'])->toBe('EC-IMPORT-002')
                ->and($failedRows[0]['errors'])->toContain(__('trans.maintenance_staff_import_duplicate_email'));

            return true;
        });

    expect(Staff::query()->where('employee_number', 'EC-IMPORT-002')->exists())->toBeFalse();
});
