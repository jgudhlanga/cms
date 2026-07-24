<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CreateStaffDto;
use App\DTO\Institution\StaffImportRowDto;
use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Enums\Shared\StatusEnum;
use App\Helpers\Helper;
use App\Http\Filters\Institution\StaffFilter;
use App\Models\Institution\Staff;
use App\Models\Users\User;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Users\interface\IUserRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffRepository extends BaseRepository implements IStaffRepository
{
    public function __construct(
        protected Staff $staff,
        protected IUserRepository $userRepository,
        protected IContactRepository $contactRepository,
        protected IAddressRepository $addressRepository,
    ) {
        parent::__construct($this->staff);
    }

    public function create(CreateStaffDto $dto): Staff
    {
        // Step 1: Create the user
        $user = $this->createUser($dto);

        // Step 2: Assign roles if provided
        if (! empty($dto->role_ids)) {
            $user->assignRole($dto->role_ids);
        }

        // Step 3: Create staff and associate user
        $staff = $this->staff->create(array_merge(
            $this->getFields($dto),
            ['user_id' => $user->id]
        ))->refresh();

        // Step 4: Associate staff with departments (single or multiple)
        if (! empty($dto->institution_department_id)) {
            $staff->institutionDepartments()->syncWithoutDetaching([$dto->institution_department_id]);
        }

        if (! empty($dto->department_ids) && is_array($dto->department_ids) && count($dto->department_ids) > 0) {
            $staff->institutionDepartments()->syncWithoutDetaching($dto->department_ids);
        }

        return $staff;
    }

    public function upsertFromImport(StaffImportRowDto $dto): Staff
    {
        return DB::transaction(function () use ($dto): Staff {
            $existing = $this->findStaffForImport($dto);

            if ($existing === null) {
                $this->assertImportCreateUniqueness($dto);

                $staff = $this->createStaffFromImport($dto);
            } else {
                $this->assertImportUpdateUniqueness($existing, $dto);

                $staff = $this->updateStaffFromImport($existing, $dto);
            }

            $this->saveImportContact($staff, $dto);
            $this->saveImportAddress($staff, $dto);
            $staff->institutionDepartments()->syncWithoutDetaching([$dto->institutionDepartmentId]);

            return $staff->refresh();
        });
    }

    public function update(Staff $staff, CreateStaffDto $dto): Staff
    {
        // Step 1: Update the associated user
        $this->updateUser($staff->user, $dto);
        $staff = tap($staff)->update($this->getFields($dto))->refresh();
        if (! empty($dto->institution_department_id)) {
            $staff->institutionDepartments()->syncWithoutDetaching([$dto->institution_department_id]);
        }

        if (! empty($dto->department_ids) && is_array($dto->department_ids) && count($dto->department_ids) > 0) {
            $staff->institutionDepartments()->sync($dto->department_ids);
        }

        return $staff;
    }

    public function allFilter($columns = ['*'], ?StaffFilter $filters = null)
    {
        return $this->staff
            ->with([
                'title',
                'gender',
                'maritalStatus',
                'race',
                'employmentType',
                'idType',
                'country',
                'religion',
                'user.status',
                'user.roles',
            ])
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function findStaffForImport(StaffImportRowDto $dto): ?Staff
    {
        $byEmployeeNumber = $this->staff->newQuery()
            ->where('tenant_id', $dto->tenantId)
            ->where('employee_number', $dto->employeeNumber)
            ->first();

        if ($byEmployeeNumber instanceof Staff) {
            return $byEmployeeNumber;
        }

        return $this->staff->newQuery()
            ->where('tenant_id', $dto->tenantId)
            ->whereHas('user', fn ($query) => $query->where('email', $dto->email))
            ->first();
    }

    private function assertImportCreateUniqueness(StaffImportRowDto $dto): void
    {
        $conflicts = [];

        if ($this->staff->newQuery()->where('tenant_id', $dto->tenantId)->where('employee_number', $dto->employeeNumber)->exists()) {
            $conflicts[] = __('trans.maintenance_staff_import_duplicate_employee_number');
        }

        if (User::query()->where('email', $dto->email)->exists()) {
            $conflicts[] = __('trans.maintenance_staff_import_duplicate_email');
        }

        if ($dto->phoneNumber !== '' && User::query()->where('phone_number', $dto->phoneNumber)->exists()) {
            $conflicts[] = __('trans.maintenance_staff_import_duplicate_phone');
        }

        if ($conflicts !== []) {
            throw ValidationException::withMessages(['import' => $conflicts]);
        }
    }

    private function assertImportUpdateUniqueness(Staff $existing, StaffImportRowDto $dto): void
    {
        $conflicts = [];

        $employeeConflict = $this->staff->newQuery()
            ->where('tenant_id', $dto->tenantId)
            ->where('employee_number', $dto->employeeNumber)
            ->where('id', '!=', $existing->id)
            ->exists();

        if ($employeeConflict) {
            $conflicts[] = __('trans.maintenance_staff_import_duplicate_employee_number');
        }

        $emailConflict = User::query()
            ->where('email', $dto->email)
            ->where('id', '!=', $existing->user_id)
            ->exists();

        if ($emailConflict) {
            $conflicts[] = __('trans.maintenance_staff_import_duplicate_email');
        }

        if ($dto->phoneNumber !== '' && User::query()
            ->where('phone_number', $dto->phoneNumber)
            ->where('id', '!=', $existing->user_id)
            ->exists()) {
            $conflicts[] = __('trans.maintenance_staff_import_duplicate_phone');
        }

        if ($conflicts !== []) {
            throw ValidationException::withMessages(['import' => $conflicts]);
        }
    }

    private function createStaffFromImport(StaffImportRowDto $dto): Staff
    {
        $userDto = new UserDto(
            tenant_id: $dto->tenantId,
            status_id: StatusEnum::ACTIVE->id(),
            first_name: $dto->firstName,
            middle_name: $dto->middleName,
            last_name: $dto->lastName,
            email: $dto->email,
            phone_number: $dto->phoneNumber,
            password: Helper::generatePasswordFromName($dto->firstName, $dto->lastName),
            role_ids: null,
        );

        $user = $this->userRepository->create($userDto);
        $user->email_verified_at = now();
        $user->save();

        if ($dto->roleNames !== []) {
            $user->syncRoles($dto->roleNames);
        }

        return $this->staff->create([
            'tenant_id' => $dto->tenantId,
            'user_id' => $user->id,
            'employee_number' => $dto->employeeNumber,
            'date_of_birth' => Carbon::parse($dto->dateOfBirth)->format('Y-m-d'),
            'marital_status_id' => $dto->maritalStatusId,
            'title_id' => $dto->titleId,
            'gender_id' => $dto->genderId,
            'employment_type_id' => $dto->employmentTypeId,
            'id_number' => $dto->idNumber,
            'passport_number' => $dto->passportNumber,
        ])->refresh();
    }

    private function updateStaffFromImport(Staff $staff, StaffImportRowDto $dto): Staff
    {
        $userDto = new UpdateUserDto(
            first_name: $dto->firstName,
            middle_name: $dto->middleName,
            last_name: $dto->lastName,
            email: $dto->email,
            phone_number: $dto->phoneNumber,
            role_ids: null,
        );

        $this->userRepository->update($staff->user, $userDto);

        if ($dto->roleNames !== []) {
            $staff->user->syncRoles($dto->roleNames);
        }

        $staff->update([
            'employee_number' => $dto->employeeNumber,
            'date_of_birth' => Carbon::parse($dto->dateOfBirth)->format('Y-m-d'),
            'marital_status_id' => $dto->maritalStatusId,
            'title_id' => $dto->titleId,
            'gender_id' => $dto->genderId,
            'employment_type_id' => $dto->employmentTypeId,
            'id_number' => $dto->idNumber,
            'passport_number' => $dto->passportNumber,
        ]);

        return $staff->refresh();
    }

    private function saveImportContact(Staff $staff, StaffImportRowDto $dto): void
    {
        $nameParts = array_filter([$dto->firstName, $dto->middleName, $dto->lastName]);
        $contactDto = new ContactDto(
            name: implode(' ', $nameParts),
            phone_number: $dto->phoneNumber,
            alt_phone_number: $dto->altPhoneNumber,
            email_address: $dto->email,
            alt_email_address: $dto->altEmailAddress,
            contact_is_main: true,
        );

        $contact = $staff->contacts()->where('contact_is_main', true)->first()
            ?? $staff->contacts->first();

        if ($contact) {
            $this->contactRepository->update($contact, $contactDto);
        } else {
            $this->contactRepository->create($staff, $contactDto);
        }
    }

    private function saveImportAddress(Staff $staff, StaffImportRowDto $dto): void
    {
        if (! $this->hasImportAddress($dto)) {
            return;
        }

        $addressDto = new AddressDto(
            address_1: $dto->address1 ?? '',
            address_2: $dto->address2 ?? '',
            address_3: $dto->address3 ?? '',
            address_4: $dto->address4,
            address_5: null,
            address_6: null,
            address_is_main: true,
        );

        $address = $staff->addresses()->where('address_is_main', true)->first()
            ?? $staff->addresses->first();

        if ($address) {
            $this->addressRepository->update($address, $addressDto);
        } else {
            $this->addressRepository->create($staff, $addressDto);
        }
    }

    private function hasImportAddress(StaffImportRowDto $dto): bool
    {
        return (bool) array_filter([
            $dto->address1,
            $dto->address2,
            $dto->address3,
            $dto->address4,
        ]);
    }

    private function getFields(CreateStaffDto $dto): array
    {
        return [
            'employee_number' => $dto->employee_number,
            'date_of_birth' => Carbon::parse($dto->date_of_birth)->format('Y-m-d'),
            'marital_status_id' => $dto->marital_status_id,
            'race_id' => $dto->race_id,
            'title_id' => $dto->title_id,
            'gender_id' => $dto->gender_id,
            'employment_type_id' => $dto->employment_type_id,
        ];
    }

    private function createUser(CreateStaffDto $dto)
    {
        $userDto = new UserDto(
            tenant_id: request()->user()->tenant_id,
            status_id: StatusEnum::ACTIVE->id(),
            first_name: $dto->first_name,
            middle_name: $dto->middle_name,
            last_name: $dto->last_name,
            email: $dto->email,
            phone_number: $dto->phone_number,
            password: Helper::generatePasswordFromName($dto->first_name, $dto->last_name),
            role_ids: $dto->role_ids,
        );
        $user = $this->userRepository->create($userDto);
        $user->email_verified_at = now();
        $user->save();

        return $user->fresh();
    }

    private function updateUser(User $user, CreateStaffDto $dto): void
    {
        $userDto = new UpdateUserDto(
            first_name: $dto->first_name,
            middle_name: $dto->middle_name,
            last_name: $dto->last_name,
            email: $dto->email,
            phone_number: $dto->phone_number,
            role_ids: $dto->role_ids,
        );
        $this->userRepository->update($user, $userDto);
    }
}
