<?php

namespace App\Repositories\Students;

use App\DTO\Students\SponsorDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Filters\Students\SponsorFilter;
use App\Models\Students\Sponsor;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Students\interface\ISponsorRepository;

class SponsorRepository extends BaseRepository implements ISponsorRepository
{

    public function __construct(
        protected Sponsor $sponsor,
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
}
