<?php

namespace App\Repositories\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Shared\NextOfKinDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\StudentProgramDto;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Shared\NextOfKin;
use App\Models\Students\Student;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use App\Repositories\Students\interface\IStudentRepository;
use Carbon\Carbon;

class StudentRepository extends BaseRepository implements IStudentRepository
{
    public function __construct(
        protected Student                   $student,
        protected IAddressRepository        $addressRepository,
        protected IContactRepository        $contactRepository,
        protected INextOfKinRepository      $nextOfKinRepository,
        protected IStudentProgramRepository $studentProgramRepository,
    )
    {
        parent::__construct($this->student);
    }

    public function create(CreateApplicationDto $dto)
    {
        $student = $this->student->create($this->getFields($dto))->refresh();
        $this->saveProgram($student, $dto);
        $this->saveContact($student, $dto);
        $this->saveAddress($student, $dto);
        $this->saveNextOfKin($student, $dto);
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
            'date_of_birth' => Carbon::parse($dto->date_of_birth)->format('Y-m-d'),
        ];
    }

    private function saveProgram(Student $student, CreateApplicationDto $dto): void
    {
        $programDto = new StudentProgramDto(
            student_id: $student->id,
            department_id: $dto->department_id,
            level_id: $dto->level_id,
            course_id: $dto->course_id,
            o_level_subjects: $dto->o_level_subjects,
            required_level_completed: $dto->required_level_completed,
            read_write_acknowledged: $dto->read_write_acknowledged,
        );
        $this->studentProgramRepository->create($programDto);
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
            alt_phone_number: $dto->alt_phone_number,
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
            address_is_main: true,
        );
        $this->addressRepository->create($student, $addressDto);
    }

    private function saveNextOfKin(Student $student, CreateApplicationDto $dto): void
    {
        $nextOfKinDto = new NextOfKinDto(
            name: $dto->next_of_kin_name,
            relationship_id: $dto->relationship_id,

        );
        $nextOfKin = $this->nextOfKinRepository->create($student, $nextOfKinDto);
        //create contact
        $this->createNextOfKinContact($nextOfKin, $dto);
        //create address
        $this->createNextOfKinAddress($nextOfKin, $dto);
    }

    private function createNextOfKinContact(NextOfKin $nextOfKin, CreateApplicationDto $dto): void
    {
        $contactDto = new ContactDto(
            name: $dto->next_of_kin_name,
            phone_number: $dto->next_of_kin_phone_number,
            alt_phone_number: null,
            email_address: null,
            alt_email_address: null,
            contact_is_main: true,
        );
        $this->contactRepository->create($nextOfKin, $contactDto);
    }

    private function createNextOfKinAddress(NextOfKin $nextOfKin, CreateApplicationDto $dto): void
    {
        $addressDto = new AddressDto(
            address_1: $dto->next_of_kin_address_1,
            address_2: $dto->next_of_kin_address_2,
            address_3: $dto->next_of_kin_address_3,
            address_4: $dto->next_of_kin_address_4,
            address_5: null,
            address_6: null,
            address_is_main: true,
        );
        $this->addressRepository->create($nextOfKin, $addressDto);
    }
}
