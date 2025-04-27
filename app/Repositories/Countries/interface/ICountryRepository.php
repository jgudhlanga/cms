<?php

namespace App\Repositories\Countries\interface;

use App\DTO\Countries\CountryDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Countries\Country;
use App\Repositories\Base\Interface\IBaseRepository;

interface ICountryRepository extends IBaseRepository
{
    public function create(CountryDto $dto);

    public function update(Country $country, CountryDto $dto);

    public function allFilter($columns = ['*'], SharedNameFilter $filters = null);
}
