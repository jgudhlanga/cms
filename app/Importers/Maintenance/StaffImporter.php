<?php

declare(strict_types=1);

namespace App\Importers\Maintenance;

use App\DTO\Institution\StaffImportRowDto;
use App\Helpers\DateHelper;
use App\Models\Acl\Role;
use App\Models\Institution\Staff;
use App\Services\Maintenance\Staff\ImportLookupMatcher;
use App\Services\Maintenance\Staff\StaffImportLookups;
use Carbon\Carbon;
use DateTimeInterface;
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

    private readonly ImportLookupMatcher $matcher;

    private readonly StaffImportLookups $lookups;

    public function __construct(private readonly int $tenantId)
    {
        $this->matcher = new ImportLookupMatcher;
        $this->lookups = new StaffImportLookups;
    }

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

    public static function normalizeCellValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return DateHelper::formatDate($value, 'Y-m-d');
        }

        if (is_int($value) || is_float($value)) {
            $stringValue = (string) $value;

            return $stringValue === '' ? null : $stringValue;
        }

        $stringValue = trim((string) $value);

        return $stringValue === '' ? null : $stringValue;
    }

    /**
     * @param  list<mixed>  $row
     * @return array<string, string|null>
     */
    public static function rowToAssociative(array $row): array
    {
        $associative = [];

        foreach (self::COLUMNS as $index => $column) {
            $associative[$column] = isset($row[$index]) ? self::normalizeCellValue($row[$index]) : null;
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
     * @param  array<string, mixed>|null  $corrections
     * @return array{
     *     dto: StaffImportRowDto|null,
     *     errors: array<string, list<string>>|null,
     *     action: 'create'|'update'|'fail'|'skip_empty',
     *     display: array<string, string|null>,
     *     fields: array<string, mixed>,
     *     needsReview: bool,
     * }
     */
    public function analyseRow(array $row, ?array $corrections = null): array
    {
        if ($this->isEmptyRow($row)) {
            return [
                'dto' => null,
                'errors' => null,
                'action' => 'skip_empty',
                'display' => $this->displayFromRow($row),
                'fields' => [],
                'needsReview' => false,
            ];
        }

        $data = self::rowToAssociative($row);
        $data = $this->applyCorrectionsToData($data, $corrections);
        $display = $this->displayFromRow($row);
        if (isset($data['EMAIL'])) {
            $display['email'] = $data['EMAIL'];
        }
        $resolved = $this->resolveLookupFields($data, $corrections);
        $fields = $resolved['fields'];
        $needsReview = $resolved['needsReview'];

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
                'fields' => $fields,
                'needsReview' => $needsReview,
            ];
        }

        $lookupErrors = $this->lookupErrors($fields);
        if ($lookupErrors !== []) {
            return [
                'dto' => null,
                'errors' => $lookupErrors,
                'action' => 'fail',
                'display' => $display,
                'fields' => $fields,
                'needsReview' => $needsReview,
            ];
        }

        try {
            $dto = $this->buildDtoFromResolved($data, $resolved['ids']);
            $action = $this->resolveAction($dto);

            return [
                'dto' => $dto,
                'errors' => null,
                'action' => $action,
                'display' => $display,
                'fields' => $fields,
                'needsReview' => $needsReview,
            ];
        } catch (ValidationException $exception) {
            return [
                'dto' => null,
                'errors' => $exception->errors(),
                'action' => 'fail',
                'display' => $display,
                'fields' => $fields,
                'needsReview' => $needsReview,
            ];
        } catch (RuntimeException $exception) {
            return [
                'dto' => null,
                'errors' => ['import' => [$exception->getMessage()]],
                'action' => 'fail',
                'display' => $display,
                'fields' => $fields,
                'needsReview' => $needsReview,
            ];
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    public function buildDto(array $data): StaffImportRowDto
    {
        $resolved = $this->resolveLookupFields($data);
        $lookupErrors = $this->lookupErrors($resolved['fields']);

        if ($lookupErrors !== []) {
            throw ValidationException::withMessages($lookupErrors);
        }

        return $this->buildDtoFromResolved($data, $resolved['ids']);
    }

    /**
     * @param  array<string, string|null>  $data
     * @param  array{
     *     titleId: int,
     *     genderId: int,
     *     maritalStatusId: int,
     *     employmentTypeId: int,
     *     institutionDepartmentId: int,
     *     roleNames: list<string>,
     * }  $resolvedIds
     */
    public function buildDtoFromResolved(array $data, array $resolvedIds): StaffImportRowDto
    {
        return new StaffImportRowDto(
            tenantId: $this->tenantId,
            employeeNumber: (string) $data['EMPLOYEE_NUMBER'],
            titleId: $resolvedIds['titleId'],
            firstName: (string) $data['FIRST_NAME'],
            middleName: $data['MIDDLE_NAME'],
            lastName: (string) $data['LAST_NAME'],
            genderId: $resolvedIds['genderId'],
            maritalStatusId: $resolvedIds['maritalStatusId'],
            employmentTypeId: $resolvedIds['employmentTypeId'],
            dateOfBirth: Carbon::parse((string) $data['DATE_OF_BIRTH'])->format('Y-m-d'),
            email: (string) $data['EMAIL'],
            phoneNumber: (string) $data['PHONE_NUMBER'],
            institutionDepartmentId: $resolvedIds['institutionDepartmentId'],
            roleNames: $resolvedIds['roleNames'],
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

    /**
     * @param  array<string, string|null>  $data
     * @param  array<string, mixed>|null  $corrections
     * @return array{
     *     fields: array<string, mixed>,
     *     ids: array{
     *         titleId: int,
     *         genderId: int,
     *         maritalStatusId: int,
     *         employmentTypeId: int,
     *         institutionDepartmentId: int,
     *         roleNames: list<string>,
     *     },
     *     needsReview: bool,
     * }
     */
    private function resolveLookupFields(array $data, ?array $corrections = null): array
    {
        $titleField = $this->resolveSingleLookupField(
            (string) ($data['TITLE'] ?? ''),
            $this->lookups->titleCandidates(),
            isset($corrections['titleId']) ? (int) $corrections['titleId'] : null,
        );

        $genderField = $this->resolveSingleLookupField(
            (string) ($data['GENDER'] ?? ''),
            $this->lookups->genderCandidates(),
            isset($corrections['genderId']) ? (int) $corrections['genderId'] : null,
        );

        $maritalStatusField = $this->resolveSingleLookupField(
            (string) ($data['MARITAL_STATUS'] ?? ''),
            $this->lookups->maritalStatusCandidates(),
            isset($corrections['maritalStatusId']) ? (int) $corrections['maritalStatusId'] : null,
        );

        $employmentTypeField = $this->resolveSingleLookupField(
            (string) ($data['EMPLOYMENT_TYPE'] ?? ''),
            $this->lookups->employmentTypeCandidates(),
            isset($corrections['employmentTypeId']) ? (int) $corrections['employmentTypeId'] : null,
        );

        $departmentField = $this->resolveSingleLookupField(
            (string) ($data['DEPARTMENT'] ?? ''),
            $this->lookups->departmentCandidates($this->tenantId),
            isset($corrections['institutionDepartmentId']) ? (int) $corrections['institutionDepartmentId'] : null,
        );

        $rolesField = $this->resolveRoleLookupFields(
            (string) ($data['ROLES'] ?? ''),
            isset($corrections['roleIds']) && is_array($corrections['roleIds'])
                ? array_map(intval(...), $corrections['roleIds'])
                : null,
        );

        $fields = [
            'title' => $titleField,
            'gender' => $genderField,
            'maritalStatus' => $maritalStatusField,
            'employmentType' => $employmentTypeField,
            'department' => $departmentField,
            'roles' => $rolesField,
        ];

        $needsReview = collect($fields)
            ->flatten(1)
            ->contains(fn (mixed $field): bool => is_array($field) && ($field['needsReview'] ?? false));

        return [
            'fields' => $fields,
            'ids' => [
                'titleId' => (int) ($titleField['resolvedId'] ?? 0),
                'genderId' => (int) ($genderField['resolvedId'] ?? 0),
                'maritalStatusId' => (int) ($maritalStatusField['resolvedId'] ?? 0),
                'employmentTypeId' => (int) ($employmentTypeField['resolvedId'] ?? 0),
                'institutionDepartmentId' => (int) ($departmentField['resolvedId'] ?? 0),
                'roleNames' => $this->roleNamesFromFields($rolesField),
            ],
            'needsReview' => $needsReview,
        ];
    }

    /**
     * @param  list<array{id: int, label: string, slug?: string|null}>  $candidates
     * @return array{
     *     raw: string,
     *     resolvedId: int|null,
     *     resolvedLabel: string|null,
     *     matchType: 'exact'|'fuzzy'|'manual'|null,
     *     needsReview: bool,
     * }
     */
    private function resolveSingleLookupField(string $raw, array $candidates, ?int $correctedId = null): array
    {
        if ($correctedId !== null) {
            foreach ($candidates as $candidate) {
                if ((int) $candidate['id'] === $correctedId) {
                    return [
                        'raw' => $raw,
                        'resolvedId' => $correctedId,
                        'resolvedLabel' => (string) $candidate['label'],
                        'matchType' => 'manual',
                        'needsReview' => false,
                    ];
                }
            }
        }

        $match = $this->matcher->match($raw, $candidates);

        if ($match === null) {
            return [
                'raw' => $raw,
                'resolvedId' => null,
                'resolvedLabel' => null,
                'matchType' => null,
                'needsReview' => false,
            ];
        }

        return [
            'raw' => $raw,
            'resolvedId' => $match['id'],
            'resolvedLabel' => $match['label'],
            'matchType' => $match['matchType'],
            'needsReview' => $match['matchType'] === 'fuzzy',
        ];
    }

    /**
     * @param  list<int>|null  $correctedRoleIds
     * @return list<array{
     *     raw: string,
     *     resolvedId: int|null,
     *     resolvedLabel: string|null,
     *     matchType: 'exact'|'fuzzy'|'manual'|null,
     *     needsReview: bool,
     * }>
     */
    private function resolveRoleLookupFields(string $rolesRaw, ?array $correctedRoleIds = null): array
    {
        if (trim($rolesRaw) === '' && ($correctedRoleIds === null || $correctedRoleIds === [])) {
            return [];
        }

        $candidates = $this->lookups->roleCandidates();

        if ($correctedRoleIds !== null && $correctedRoleIds !== []) {
            return array_values(array_filter(array_map(function (int $roleId) use ($candidates, $rolesRaw): ?array {
                foreach ($candidates as $candidate) {
                    if ((int) $candidate['id'] === $roleId) {
                        return [
                            'raw' => $rolesRaw,
                            'resolvedId' => $roleId,
                            'resolvedLabel' => (string) $candidate['label'],
                            'matchType' => 'manual',
                            'needsReview' => false,
                        ];
                    }
                }

                $role = Role::query()->find($roleId);

                if ($role === null) {
                    return null;
                }

                return [
                    'raw' => $rolesRaw,
                    'resolvedId' => $role->id,
                    'resolvedLabel' => (string) $role->name,
                    'matchType' => 'manual',
                    'needsReview' => false,
                ];
            }, $correctedRoleIds)));
        }

        $roleTokens = array_values(array_filter(array_map(
            static fn (string $token): string => trim($token),
            explode(',', $rolesRaw),
        )));

        $fields = [];

        foreach ($roleTokens as $token) {
            $match = $this->matcher->match($token, $candidates);

            if ($match === null) {
                $fields[] = [
                    'raw' => $token,
                    'resolvedId' => null,
                    'resolvedLabel' => null,
                    'matchType' => null,
                    'needsReview' => false,
                ];

                continue;
            }

            $fields[] = [
                'raw' => $token,
                'resolvedId' => $match['id'],
                'resolvedLabel' => $match['label'],
                'matchType' => $match['matchType'],
                'needsReview' => $match['matchType'] === 'fuzzy',
            ];
        }

        return $fields;
    }

    /**
     * @param  list<array<string, mixed>>  $roleFields
     * @return list<string>
     */
    private function roleNamesFromFields(array $roleFields): array
    {
        return array_values(array_filter(array_map(
            static fn (array $field): ?string => isset($field['resolvedLabel']) ? (string) $field['resolvedLabel'] : null,
            $roleFields,
        )));
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, list<string>>
     */
    private function lookupErrors(array $fields): array
    {
        $errors = [];

        $requiredFields = [
            'title' => 'TITLE',
            'gender' => 'GENDER',
            'maritalStatus' => 'MARITAL_STATUS',
            'employmentType' => 'EMPLOYMENT_TYPE',
            'department' => 'DEPARTMENT',
        ];

        foreach ($requiredFields as $key => $label) {
            /** @var array<string, mixed> $field */
            $field = $fields[$key];
            if (($field['resolvedId'] ?? null) === null) {
                $errors[$label] = [__('trans.maintenance_staff_import_lookup_not_found', [
                    'field' => $label,
                    'value' => (string) ($field['raw'] ?? ''),
                ])];
            }
        }

        /** @var list<array<string, mixed>> $roleFields */
        $roleFields = $fields['roles'] ?? [];
        foreach ($roleFields as $roleField) {
            if (($roleField['resolvedId'] ?? null) === null) {
                $errors['ROLES'] ??= [];
                $errors['ROLES'][] = __('trans.maintenance_staff_import_role_not_found', [
                    'role' => (string) ($roleField['raw'] ?? ''),
                ]);
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, string|null>  $data
     * @param  array<string, mixed>|null  $corrections
     * @return array<string, string|null>
     */
    private function applyCorrectionsToData(array $data, ?array $corrections): array
    {
        if ($corrections === null) {
            return $data;
        }

        if (isset($corrections['email']) && is_string($corrections['email']) && trim($corrections['email']) !== '') {
            $data['EMAIL'] = trim($corrections['email']);
        }

        return $data;
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
}
