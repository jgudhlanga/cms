<?php

namespace App\Http\Controllers\Institution\Config;

use App\DTO\Institution\AssessmentTypeDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Institution\AssessmentTypeRequest;
use App\Http\Resources\Institution\AssessmentTypeResource;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\ModeOfStudy;
use App\Repositories\Institution\interface\IAssessmentTypeRepository;
use Inertia\Inertia;

class AssessmentTypeController extends Controller
{
    public function __construct(protected IAssessmentTypeRepository $repository) {}

    public function index(SharedNameFilter $filters)
    {
        $this->authorize('viewSettings');
        $assessmentTypes = AssessmentTypeResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('institution/dropdowns/assessment-types/Index', [
            'assessmentTypes' => $assessmentTypes,
            'modesOfStudy' => ModeOfStudy::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    public function create()
    {
        $this->authorize('createSettings');
    }

    public function store(AssessmentTypeRequest $request)
    {
        $this->authorize('createSettings');
        $this->repository->create(AssessmentTypeDto::fromAssessmentTypeRequest($request));
    }

    public function show(AssessmentType $assessmentType)
    {
        //
    }

    public function edit(AssessmentType $assessmentType)
    {
        //
    }

    public function update(AssessmentTypeRequest $request, AssessmentType $assessmentType)
    {
        $this->authorize('updateInstitutionSettings');
        $this->repository->update($assessmentType, AssessmentTypeDto::fromAssessmentTypeRequest($request));
    }

    public function destroy(AssessmentType $assessmentType)
    {
        $this->authorize('deleteInstitutionSettings');
        $this->repository->delete($assessmentType);
    }

    public function restore(string $id)
    {
        $assessmentType = $this->repository->findTrashed($id);
        $this->authorize('restoreInstitutionSettings');
        $this->repository->restore($assessmentType);
    }

    public function forceDelete(AssessmentType $assessmentType)
    {
        $this->authorize('forceDeleteInstitutionSettings');
        $this->repository->delete($assessmentType, true);
    }
}
