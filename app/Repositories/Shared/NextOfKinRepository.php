<?php

namespace App\Repositories\Shared;


use App\DTO\Shared\NextOfKinDto;
use App\Models\Shared\NextOfKin;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NextOfKinRepository extends BaseRepository implements INextOfKinRepository
{
    public function __construct(
        protected NextOfKin $nextOfKin)
    {
        parent::__construct($this->nextOfKin);
    }

    public function create(Model $model, NextOfKinDto $dto): NextOfKin
    {
        $nextOfKin = NextOfKin::create(
            array_merge([
                'tenant_id' => $model->tenant_id ?? @Auth::user()->tenant_id,
                'kinnable_id' => $model->id,
                'kinnable_type' => get_class($model),
            ],
                $this->getFields($dto))
        );
        // create or update address
        $this->createOrUpdateAddress($nextOfKin, $dto);
        // create or update contact
        $this->createOrUpdateContact($nextOfKin, $dto);
        return $nextOfKin;
    }

    public function update(NextOfKin $nextOfKin, NextOfKinDto $dto): NextOfKin
    {
        $nextOfKin = tap($nextOfKin)->update($this->getFields($dto));
        // create or update address
        $this->createOrUpdateAddress($nextOfKin, $dto);
        // create or update contact
        $this->createOrUpdateContact($nextOfKin, $dto);

        return $nextOfKin;
    }

    private function getFields(NextOfKinDto $dto): array
    {
        return [
            'name' => $dto->name,
            'relationship_id' => $dto->relationship_id,
        ];
    }

    private function createOrUpdateAddress(NextOfKin $nextOfKin, NextOfKinDto $dto): void
    {
        # Update the first address if any address fields are present
        if ($dto->address_1 || $dto->address_2 || $dto->address_3 || $dto->address_4) {
            $address = $nextOfKin->addresses()->firstOrCreate(); // create if not exists

            $address->update([
                'address_1' => $dto->address_1 ?? $address->address_1,
                'address_2' => $dto->address_2 ?? $address->address_2,
                'address_3' => $dto->address_3 ?? $address->address_3,
                'address_4' => $dto->address_4 ?? $address->address_4,
                'address_is_main' => 1,
            ]);
        }
    }

    private function createOrUpdateContact(NextOfKin $nextOfKin, NextOfKinDto $dto): void
    {
        if ($dto->phone_number) {
            $contact = $nextOfKin->contacts()->firstOrCreate(); // create if not exists
            $contact->update([
                'name' => $nextOfKin->name,
                'phone_number' => $dto->phone_number,
                'contact_is_main' => 1,
            ]);
        }
    }
}
