<?php

namespace App\Http\Controllers\Institution\Dropdowns;

use App\DTO\Institution\SubjectDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\SubjectRequest;
use App\Http\Requests\Shared\PositionRequest;
use App\Http\Resources\Institution\SubjectResource;
use App\Models\Institution\Subject;
use App\Repositories\Institution\interface\ISubjectRepository;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function __construct(protected ISubjectRepository $repository)
    {
    }

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewAny', Subject::class);
        $subjects = SubjectResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('institution/dropdowns/subjects/Index', [
            'subjects' => $subjects,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Subject::class);
    }

    public function store(SubjectRequest $request)
    {
        $this->authorize('create', Subject::class);
        $this->repository->create(SubjectDto::fromSubjectRequest($request));
    }

    public function show(Subject $subject)
    {
        //
    }

    public function edit(Subject $subject)
    {
        //
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        $this->authorize('update', $subject);
        $this->repository->update($subject, SubjectDto::fromSubjectRequest($request));
    }

    public function movePosition(PositionRequest $request, Subject $subject): void
    {
        $this->authorize('update', $subject);
        $this->repository->movePosition($subject, $request);
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('delete', $subject);
        $this->repository->delete($subject);
    }

    public function restore(string $id)
    {
        $subject = $this->repository->findTrashed($id);
        $this->authorize('restore', $subject);
        $this->repository->restore($subject);
    }

    public function forceDelete(Subject $subject)
    {
        $this->authorize('forceDelete', $subject);
        $this->repository->delete($subject, true);
    }
}
