<?php

namespace App\Repositories\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Students\CreateApplicationDto;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Students\Student;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Students\interface\IStudentRepository;

class StudentRepository extends BaseRepository implements IStudentRepository
{
    public function __construct(
        protected Student                  $student,
        protected IAddressRepository  $addressRepository,
        protected IContactRepository      $contactRepository,
    )
    {
        parent::__construct($this->student);
    }
    public function create(CreateApplicationDto $dto)
    {
        $student = $this->student->create($this->getFields($dto))->refresh();
        $this->saveContact($student, $dto);
        $this->saveAddress($student, $dto);
        return $student->refresh();
    }

    public function update(Student $student, CreateApplicationDto $dto)
    {
        return tap($student)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], ?StudentFilter $filters = null)
    {
        return $this->student
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(CreateApplicationDto $dto): array
    {
        return [
            'user_id' => $dto->user_id,
            'title_id' => $dto->title_id,
            'gender_id' => $dto->gender_id,
            'marital_status_id' => $dto->marital_status_id,
            'race_id' => $dto->race_id,
            'id_type' => $dto->id_type,
            'id_number' => $dto->id_number,
            'passport_number' => $dto->passport_number,
            'country_id' => $dto->country_id,
            'study_permit_number' => $dto->study_permit_number,
            'date_of_birth' => $dto->date_of_birth,
        ];
    }
    private function saveContact(Student $student, CreateApplicationDto $dto): void
    {
        $nameParts = array_filter([
            $dto->first_name,
            $dto->middle_name,
            $dto->last_name,
        ]);
        $contactDto = new ContactDto(
            name: implode(' ', $nameParts),
            phone_number: $dto->phone_number,
            alt_phone_number: null,
            email_address: $dto->email,
            alt_email_address: null,
            contact_is_main: true,
        );
        $this->contactRepository->create($student, $contactDto);
    }

    private function saveAddress(Student $student, CreateApplicationDto $dto): void
    {
        $addressDto = new AddressDto(
            address_1: $dto->address_1,
            address_2: $dto->address_2,
            address_3: $dto->address_3,
            address_4: $dto->address_4,
            address_5: null,
            address_6: null,
            address_is_main:true,
        );
        $this->addressRepository->create($student, $addressDto);
    }
}
