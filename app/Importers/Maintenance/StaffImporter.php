<?php

declare(strict_types=1);

namespace App\Importers\Maintenance;

use App\DTO\Institution\StaffImportRowDto;
use App\Models\Acl\Role;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LaravelIngest\Contracts\IngestDefinition;
use LaravelIngest\IngestConfig;
use RuntimeException;

class StaffImporter implements IngestDefinition
{
    public const string IMPORTER_NAME = 'staff-import';

    public const int HEADER_ROW = 5;

    public const int DATA_START_ROW = 6;

    /** @var list<string> */
    public const array COLUMNS = [
        'EMPLOYEE_NUMBER',
        'TITLE',
        'FIRST_NAME',
        'MIDDLE_NAME',
        'LAST_NAME',
        'GENDER',
        'MARITAL_STATUS',
        'EMPLOYMENT_TYPE',
        'DATE_OF_BIRTH',
        'EMAIL',
        'PHONE_NUMBER',
        'DEPARTMENT',
        'ROLES',
        'ID_NUMBER',
        'PASSPORT_NUMBER',
        'ALT_PHONE_NUMBER',
        'ALT_EMAIL_ADDRESS',
        'ADDRESS_1',
        'ADDRESS_2',
        'ADDRESS_3',
        'ADDRESS_4',
    ];

    /** @var list<string> */
    public const array ALLOWED_ROLE_SLUGS = [
        'head-of-department',
        'head-of-division',
        'lecturer',
        'lecturer-in-charge',
        'senior-lecturer',
    ];

    public function __construct(private readonly int $tenantId) {}

    public function getConfig(): IngestConfig
    {
        return IngestConfig::for(Staff::class);
    }

    /**
     * @param  list<mixed>  $headerRow
     */
    public static function isHeaderRow(array $headerRow): bool
    {
        $normalized = array_map(
            static fn (mixed $value): string => strtoupper(trim((string) $value)),
            $headerRow,
        );

        return ($normalized[0] ?? '') === 'EMPLOYEE_NUMBER';
    }

    /**
     * @param  list<mixed>  $row
     * @return array<string, string|null>
     */
    public static function rowToAssociative(array $row): array
    {
        $associative = [];

        foreach (self::COLUMNS as $index => $column) {
            $associative[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
            if ($associative[$column] === '') {
                $associative[$column] = null;
            }
        }

        return $associative;
    }

    /**
     * @param  list<mixed>  $row
     */
    public function isEmptyRow(array $row): bool
    {
        $data = self::rowToAssociative($row);

        return trim((string) ($data['EMPLOYEE_NUMBER'] ?? '')) === ''
            && trim((string) ($data['EMAIL'] ?? '')) === '';
    }

    /**
     * @param  list<mixed>  $row
     * @return array{
     *     dto: StaffImportRowDto|null,
     *     errors: array<string, list<string>>|null,
     *     action: 'create'|'update'|'fail'|'skip_empty',
     *     display: array<string, string|null>
     * }
     */
    public function analyseRow(array $row): array
    {
        if ($this->isEmptyRow($row)) {
            return [
                'dto' => null,
                'errors' => null,
                'action' => 'skip_empty',
                'display' => $this->displayFromRow($row),
            ];
        }

        $data = self::rowToAssociative($row);
        $display = $this->displayFromRow($row);

        $validator = Validator::make($data, [
            'EMPLOYEE_NUMBER' => ['required', 'string', 'max:255'],
            'TITLE' => ['required', 'string'],
            'FIRST_NAME' => ['required', 'string', 'max:255'],
            'LAST_NAME' => ['required', 'string', 'max:255'],
            'GENDER' => ['required', 'string'],
            'MARITAL_STATUS' => ['required', 'string'],
            'EMPLOYMENT_TYPE' => ['required', 'string'],
            'DATE_OF_BIRTH' => ['required', 'date'],
            'EMAIL' => ['required', 'email', 'max:255'],
            'PHONE_NUMBER' => ['required', 'string', 'max:30'],
            'DEPARTMENT' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return [
                'dto' => null,
                'errors' => $validator->errors()->toArray(),
                'action' => 'fail',
                'display' => $display,
            ];
        }

        try {
            $dto = $this->buildDto($data);
            $action = $this->resolveAction($dto);

            return [
                'dto' => $dto,
                'errors' => null,
                'action' => $action,
                'display' => $display,
            ];
        } catch (ValidationException $exception) {
            return [
                'dto' => null,
                'errors' => $exception->errors(),
                'action' => 'fail',
                'display' => $display,
            ];
        } catch (RuntimeException $exception) {
            return [
                'dto' => null,
                'errors' => ['import' => [$exception->getMessage()]],
                'action' => 'fail',
                'display' => $display,
            ];
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function buildDto(array $data): StaffImportRowDto
    {
        $roleSlugs = $this->parseRoles((string) ($data['ROLES'] ?? ''));

        return new StaffImportRowDto(
            tenantId: $this->tenantId,
            employeeNumber: (string) $data['EMPLOYEE_NUMBER'],
            titleId: $this->resolveTitleId((string) $data['TITLE']),
            firstName: (string) $data['FIRST_NAME'],
            middleName: $data['MIDDLE_NAME'],
            lastName: (string) $data['LAST_NAME'],
            genderId: $this->resolveGenderId((string) $data['GENDER']),
            maritalStatusId: $this->resolveMaritalStatusId((string) $data['MARITAL_STATUS']),
            employmentTypeId: $this->resolveEmploymentTypeId((string) $data['EMPLOYMENT_TYPE']),
            dateOfBirth: Carbon::parse((string) $data['DATE_OF_BIRTH'])->format('Y-m-d'),
            email: (string) $data['EMAIL'],
            phoneNumber: (string) $data['PHONE_NUMBER'],
            institutionDepartmentId: $this->resolveInstitutionDepartmentId((string) $data['DEPARTMENT']),
            roleSlugs: $roleSlugs,
            idNumber: $data['ID_NUMBER'],
            passportNumber: $data['PASSPORT_NUMBER'],
            altPhoneNumber: $data['ALT_PHONE_NUMBER'],
            altEmailAddress: $data['ALT_EMAIL_ADDRESS'],
            address1: $data['ADDRESS_1'],
            address2: $data['ADDRESS_2'],
            address3: $data['ADDRESS_3'],
            address4: $data['ADDRESS_4'],
        );
    }

    /**
     * @param  list<mixed>  $row
     * @return array<string, string|null>
     */
    public function displayFromRow(array $row): array
    {
        $data = self::rowToAssociative($row);

        return [
            'employeeNumber' => $data['EMPLOYEE_NUMBER'],
            'fullName' => trim(implode(' ', array_filter([
                $data['FIRST_NAME'],
                $data['MIDDLE_NAME'],
                $data['LAST_NAME'],
            ]))),
            'email' => $data['EMAIL'],
            'department' => $data['DEPARTMENT'],
        ];
    }

    private function resolveAction(StaffImportRowDto $dto): string
    {
        $existing = Staff::query()
            ->where('tenant_id', $dto->tenantId)
            ->where('employee_number', $dto->employeeNumber)
            ->exists();

        if ($existing) {
            return 'update';
        }

        $existingByEmail = Staff::query()
            ->where('tenant_id', $dto->tenantId)
            ->whereHas('user', fn ($query) => $query->where('email', $dto->email))
            ->exists();

        return $existingByEmail ? 'update' : 'create';
    }

    /**
     * @return list<string>
     */
    private function parseRoles(string $roles): array
    {
        if (trim($roles) === '') {
            return [];
        }

        $slugs = array_values(array_filter(array_map(
            static fn (string $slug): string => strtolower(trim($slug)),
            explode(',', $roles),
        )));

        foreach ($slugs as $slug) {
            if (! in_array($slug, self::ALLOWED_ROLE_SLUGS, true)) {
                throw ValidationException::withMessages([
                    'ROLES' => [__('trans.maintenance_staff_import_invalid_role', ['role' => $slug])],
                ]);
            }

            if (! Role::query()->where('name', $slug)->exists()) {
                throw ValidationException::withMessages([
                    'ROLES' => [__('trans.maintenance_staff_import_role_not_found', ['role' => $slug])],
                ]);
            }
        }

        return $slugs;
    }

    private function resolveTitleId(string $title): int
    {
        $record = Title::query()
            ->whereRaw('LOWER(name) = ?', [self::normalize($title)])
            ->first();

        if ($record === null) {
            throw new RuntimeException(__('trans.maintenance_staff_import_lookup_not_found', [
                'field' => 'TITLE',
                'value' => $title,
            ]));
        }

        return $record->id;
    }

    private function resolveGenderId(string $gender): int
    {
        $record = Gender::query()
            ->whereRaw('LOWER(title) = ?', [self::normalize($gender)])
            ->first();

        if ($record === null) {
            throw new RuntimeException(__('trans.maintenance_staff_import_lookup_not_found', [
                'field' => 'GENDER',
                'value' => $gender,
            ]));
        }

        return $record->id;
    }

    private function resolveMaritalStatusId(string $maritalStatus): int
    {
        $record = MaritalStatus::query()
            ->whereRaw('LOWER(title) = ?', [self::normalize($maritalStatus)])
            ->first();

        if ($record === null) {
            throw new RuntimeException(__('trans.maintenance_staff_import_lookup_not_found', [
                'field' => 'MARITAL_STATUS',
                'value' => $maritalStatus,
            ]));
        }

        return $record->id;
    }

    private function resolveEmploymentTypeId(string $employmentType): int
    {
        $record = EmploymentType::query()
            ->whereRaw('LOWER(name) = ?', [self::normalize($employmentType)])
            ->first();

        if ($record === null) {
            throw new RuntimeException(__('trans.maintenance_staff_import_lookup_not_found', [
                'field' => 'EMPLOYMENT_TYPE',
                'value' => $employmentType,
            ]));
        }

        return $record->id;
    }

    private function resolveInstitutionDepartmentId(string $department): int
    {
        $institutionDepartment = InstitutionDepartment::query()
            ->where('tenant_id', $this->tenantId)
            ->whereHas('department', function ($query) use ($department): void {
                $query->whereRaw('LOWER(name) = ?', [self::normalize($department)]);
            })
            ->first();

        if ($institutionDepartment === null) {
            throw new RuntimeException(__('trans.maintenance_staff_import_lookup_not_found', [
                'field' => 'DEPARTMENT',
                'value' => $department,
            ]));
        }

        return $institutionDepartment->id;
    }

    private static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }
}
