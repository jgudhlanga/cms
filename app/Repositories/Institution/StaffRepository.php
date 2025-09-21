<?php

namespace App\Repositories\Institution;

use App\DTO\Institution\CreateStaffDto;
use App\DTO\Users\UpdateUserDto;
use App\DTO\Users\UserDto;
use App\Models\Users\User;
use App\Enums\Shared\StatusEnum;
use App\Helpers\Helper;
use App\Http\Filters\Institution\StaffFilter;
use App\Models\Institution\Staff;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Institution\interface\IStaffRepository;
use App\Repositories\Users\interface\IUserRepository;
use Carbon\Carbon;

class StaffRepository extends BaseRepository implements IStaffRepository
{
    public function __construct(protected Staff $staff, protected IUserRepository $userRepository)
    {
        parent::__construct($this->staff);
    }

    public function create(CreateStaffDto $dto): Staff
    {
        # Step 1: Create the user
        $user = $this->createUser($dto);

        // Step 2: Assign roles if provided
        if (!empty($dto->role_ids)) {
            $user->assignRole($dto->role_ids);
        }

        # Step 3: Create staff and associate user
        $staff = $this->staff->create(array_merge(
            $this->getFields($dto),
            ['user_id' => $user->id]
        ))->refresh();

        # Step 4: Associate staff with departments (single or multiple)
        if (!empty($dto->institution_department_id)) {
            $staff->institutionDepartments()->syncWithoutDetaching([$dto->institution_department_id]);
        }

        return $staff;
    }

    public function update(Staff $staff, CreateStaffDto $dto): Staff
    {
        // Step 1: Update the associated user
        $this->updateUser($staff->user, $dto);
        return tap($staff)->update($this->getFields($dto))->refresh();
    }

    public function allFilter($columns = ['*'], StaffFilter $filters = null)
    {
        return $this->staff
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
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
        );
        return $this->userRepository->create($userDto);
    }

    private function updateUser(User $user, CreateStaffDto $dto): void
    {
        $userDto = new UpdateUserDto(
            first_name: $dto->first_name,
            middle_name: $dto->middle_name,
            last_name: $dto->last_name,
            email: $dto->email,
            phone_number: $dto->phone_number,
        );
        $this->userRepository->update($user, $userDto);
    }
}
