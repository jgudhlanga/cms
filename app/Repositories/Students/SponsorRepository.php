<?php

namespace App\Repositories\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Students\SponsorDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Students\Sponsor;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Students\interface\ISponsorRepository;

class SponsorRepository extends BaseRepository implements ISponsorRepository
{

    public function __construct(
        protected Sponsor            $sponsor,
        protected IAddressRepository $addressRepository,
        protected IContactRepository $contactRepository,
    )
    {
        parent::__construct($this->sponsor);
    }

    public function create(SponsorDto $dto)
    {
        return $this->sponsor->create($this->getFields($dto))->refresh();
    }

    public function update(Sponsor $sponsor, SponsorDto $dto)
    {
        return tap($sponsor)->update($this->getFields($dto));
    }

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null)
    {
        return $this->sponsor
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function getFields(SponsorDto $dto): array
    {
        return [
            'name' => $dto->name,
            'student_id' => $dto->student_id,
            'sponsor_type_id' => $dto->sponsor_type_id,
            'phone_number' => $dto->phone_number,
            'email' => $dto->email,
            'address_1' => $dto->address_1,
            'address_2' => $dto->address_2,
            'address_3' => $dto->address_3,
            'address_4' => $dto->address_4,
        ];
    }

    private function saveContact(Sponsor $sponsor, SponsorDto $dto): void
    {
        $contactDto = new ContactDto(
            name: $dto->name,
            phone_number: $dto->phone_number,
            alt_phone_number: null,
            email_address: $dto->email,
            alt_email_address: null,
            contact_is_main: true,
        );
        $this->contactRepository->create($sponsor, $contactDto);
    }

    private function saveAddress(Sponsor $sponsor, SponsorDto $dto): void
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
        $this->addressRepository->create($sponsor, $addressDto);
    }
}
