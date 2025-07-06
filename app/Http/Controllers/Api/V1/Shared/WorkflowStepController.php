<?php

namespace App\Http\Controllers\Api\V1\Shared;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\Shared\WorkflowStepResource;
use App\Repositories\Shared\interface\IWorkflowStepRepository;
use Illuminate\Http\Request;

class WorkflowStepController extends Controller
{
    public function __construct(protected IWorkflowStepRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return WorkflowStepResource::collection($this->repository->allFilter(['*'], $filters));
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
