<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\SponsorDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Students\Sponsor;
use App\Repositories\Base\Interface\IBaseRepository;

interface ISponsorRepository extends IBaseRepository
{
    public function create(SponsorDto $dto);

    public function update(Sponsor $sponsor, SponsorDto $dto);

    public function allFilter($columns = ['*'], ?SharedNameFilter $filters = null);
}
