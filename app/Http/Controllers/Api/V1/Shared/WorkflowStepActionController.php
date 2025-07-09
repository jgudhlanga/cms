<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedTitleFilter;
use App\Http\Resources\Shared\WorkflowStepActionResource;
use App\Repositories\Shared\interface\IWorkflowStepActionRepository;
use Illuminate\Http\Request;

class WorkflowStepActionController extends Controller
{
    public function __construct(protected IWorkflowStepActionRepository $repository)
    {

    }

    public function index(SharedTitleFilter $filters)
    {
        return WorkflowStepActionResource::collection($this->repository->allFilter(['*'], $filters));
    }

    public function store(Request $request)
    {
    }

    public function show(string $id)
    {
    }

    public function update(Request $request, string $id)
    {
    }

    public function destroy(string $id)
    {
    }
}
