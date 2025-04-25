<?php

namespace App\Repositories\Countries;


use App\DTO\Countries\CountryDto;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Models\Countries\Country;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Countries\interface\ICountryRepository;


class CountryRepository extends BaseRepository implements ICountryRepository
{
	public function __construct(protected Country $country)
	{
		parent::__construct($this->country);
	}

	public function create(CountryDto $dto): Country
	{
		return $this->country->create([
			'name' => $dto->name,
			'flag' => $dto->flag,
		])->refresh();
	}

	public function update(Country $country, CountryDto $dto): Country
	{
		return tap($country)->update([
			'name' => $dto->name,
			'flag' => $dto->flag,
		]);
	}

	public function allFilter($columns = ['*'], SharedNameFilter $filters = null)
	{
		return $this->country
			->select($columns)
			->filter($filters)
			->orderBy('name')
			->orderBy('deleted_at')
			->paginate()
			->withQueryString();
	}
}
