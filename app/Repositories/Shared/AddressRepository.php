<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\AddressDto;
use App\Models\Shared\Address;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AddressRepository extends BaseRepository implements IAddressRepository
{
    public function __construct(protected Address $address)
    {
        parent::__construct($this->address);
    }

    public function create(Model $model, AddressDto $dto): Address
    {
        $this->handleMainAddress($model, $dto);
        return Address::create(
            array_merge([
                'tenant_id' => $model->tenant_id ?? @Auth::user()->tenant_id,
                'addressable_id' => $model->id,
                'addressable_type' => get_class($model),
            ],
                $this->getFields($dto))
        );
    }

    public function update(Address $address, AddressDto $dto): Address
    {
        $this->handleMainAddress($address, $dto);
        return tap($address)->update($this->getFields($dto));
    }

    private function getFields(AddressDto $dto): array
    {
        return [
            'address_1' => $dto->address_1,
            'address_2' => $dto->address_2,
            'address_3' => $dto->address_3,
            'address_4' => $dto->address_4,
            'address_5' => $dto->address_5,
            'address_6' => $dto->address_6,
            'address_is_main' => $dto->address_is_main ?? false,
        ];
    }

    private function handleMainAddress(Model $model, AddressDto $dto): void
    {
        if ($dto->address_is_main) {
            Address::query()
                ->where('addressable_type', get_class($model))
                ->where('addressable_id', $model->id)
                ->update(['address_is_main' => false]);
        }
    }
}
