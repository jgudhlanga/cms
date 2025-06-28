<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\SponsorTypeDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\SponsorType;
use App\Repositories\Base\Interface\IBaseRepository;

interface ISponsorTypeRepository extends IBaseRepository
{
    public function create(SponsorTypeDto $dto);

    public function update(SponsorType $sponsorType, SponsorTypeDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
