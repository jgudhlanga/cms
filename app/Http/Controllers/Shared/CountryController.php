<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Countries\CountryDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\CountryRequest;
use App\Http\Resources\Shared\CountryResource;
use App\Models\Shared\Country;
use App\Repositories\Shared\interface\ICountryRepository;
use Inertia\Inertia;

class CountryController extends Controller
{
	public function __construct(protected ICountryRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewSettings');
		$countries = CountryResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/countries/Index', [
			'countries' => $countries,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(CountryRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(CountryDto::fromCountryRequest($request));
	}

	public function show(Country $country)
	{
		//
	}

	public function edit(Country $country)
	{
		//
	}

	public function update(CountryRequest $request, Country $country)
	{
		$this->authorize('updateSettings');
		$this->repository->update($country, CountryDto::fromCountryRequest($request));
	}

	public function destroy(Country $country)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($country);
	}

	public function restore(string $id)
	{
		$model = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($model);
	}

	public function forceDelete(Country $country)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($country, true);
	}
}
