<?php

namespace App\Http\Controllers\Titles;

use App\DTO\Titles\CourseDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Titles\TitleRequest;
use App\Http\Resources\Titles\TitleResource;
use App\Models\Titles\Title;
use App\Repositories\Titles\interface\ICourseRepository;
use Inertia\Inertia;

class TitleController extends Controller
{
	public function __construct(protected ICourseRepository $repository)
	{
	}

	public function index(SharedNameFilter $filters)
	{
		$this->authorize('viewSettings');
		$titles = TitleResource::collection($this->repository->allFilter(['*'], $filters));
		return Inertia::render('titles/Index', [
			'titles' => $titles,
			'filters' => request()->only(['search', 'trashed']),
			'trashedCount' => $this->repository->allTrashed()->count(),
		]);
	}

	public function create()
	{
		$this->authorize('createSettings');
	}

	public function store(TitleRequest $request)
	{
		$this->authorize('createSettings');
		$this->repository->create(CourseDto::fromTitleRequest($request));
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
		$this->authorize('updateSettings');
		$this->repository->update($title, CourseDto::fromTitleRequest($request));
	}

	public function destroy(Title $title)
	{
		$this->authorize('deleteSettings');
		$this->repository->delete($title);
	}

	public function restore(string $id)
	{
		$title = $this->repository->findTrashed($id);
		$this->authorize('restoreSettings');
		$this->repository->restore($title);
	}

	public function forceDelete(Title $title)
	{
		$this->authorize('forceDeleteSettings');
		$this->repository->delete($title, true);
	}
}
