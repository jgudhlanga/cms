<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\TitleDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Shared\TitleRequest;
use App\Http\Resources\Shared\TitleResource;
use App\Models\Shared\Title;
use App\Repositories\Shared\interface\ITitleRepository;
use Inertia\Inertia;

class TitleController extends Controller
{
	public function __construct(protected ITitleRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewAny', Title::class);
		$titles = TitleResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('shared/titles/Index', [
			'titles' => $titles,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('create', Title::class);
	}

	public function store(TitleRequest $request)
	{
		$this->authorize('create', Title::class);
		$this->repository->create(TitleDto::fromTitleRequest($request));
	}

	public function show(Title $title)
	{
		//
	}

	public function edit(Title $title)
	{
		//
	}

	public function update(TitleRequest $request, Title $title)
	{
		$this->authorize('update', $title);
		$this->repository->update($title, TitleDto::fromTitleRequest($request));
	}

	public function destroy(Title $title)
	{
		$this->authorize('delete', $title);
		$this->repository->delete($title);
	}

	public function restore(string $id)
	{
		$title = $this->repository->findTrashed($id);
		$this->authorize('restore', $title);
		$this->repository->restore($title);
	}

	public function forceDelete(Title $title)
	{
		$this->authorize('forceDelete', $title);
		$this->repository->delete($title, true);
	}
}
