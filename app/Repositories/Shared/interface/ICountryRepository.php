<?php

namespace App\Repositories\Shared\interface;

use App\DTO\Shared\CountryDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Shared\Country;
use App\Repositories\Base\Interface\IBaseRepository;

interface ICountryRepository extends IBaseRepository
{
    public function create(CountryDto $dto);

    public function update(Country $country, CountryDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
