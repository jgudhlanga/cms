<?php

namespace App\Http\Controllers\Api\V1\AcademicLevels;

use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Resources\AcademicLevels\AcademicLevelResource;
use App\Repositories\AcademicLevels\interface\IAcademicLevelRepository;
use Illuminate\Http\Request;

class AcademicLevelController extends Controller
{
    public function __construct(protected IAcademicLevelRepository $repository)
    {

    }

    public function index(SharedNameFilter $filters)
    {
        return AcademicLevelResource::collection($this->repository->allFilter(['*'], $filters));
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
