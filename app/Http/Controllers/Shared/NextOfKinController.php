<?php

namespace App\Http\Controllers\Shared;

use App\DTO\Shared\NextOfKinDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\NextOfKinRequest;
use App\Models\Shared\NextOfKin;
use App\Repositories\Shared\interface\INextOfKinRepository;

class NextOfKinController extends Controller
{
    public function __construct(protected INextOfKinRepository $repository)
    {
    }


    public function update(NextOfKinRequest $request, NextOfKin $nextOfKin)
    {
        $this->authorize('update', $nextOfKin);
        $this->repository->update($nextOfKin, NextOfKinDto::fromNextOfKinRequest($request));
    }

    public function destroy(NextOfKin $nextOfKin)
    {
        $this->authorize('delete', $nextOfKin);
        $this->repository->delete($nextOfKin);
    }

    public function restore(string $id)
    {
        $nextOfKin = $this->repository->findTrashed($id);
        $this->authorize('restore', $nextOfKin);
        $this->repository->restore($nextOfKin);
    }

    public function forceDelete(NextOfKin $nextOfKin)
    {
        $this->authorize('forceDelete', $nextOfKin);
        $this->repository->delete($nextOfKin, true);
    }
}
