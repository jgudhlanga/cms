<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\StudentEnrolmentStatusDto;
use App\Http\Controllers\Controller;
use App\Http\Filters\Shared\SharedNameFilter;
use App\Http\Requests\Students\StudentEnrolmentStatusRequest;
use App\Http\Resources\Students\StudentEnrolmentStatusResource;
use App\Models\Students\StudentEnrolmentStatus;
use App\Repositories\Students\interface\IStudentEnrolmentStatusRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrolmentStatusController extends Controller
{
    public function __construct(protected IStudentEnrolmentStatusRepository $repository) {}

    /**
     * @throws AuthorizationException
     */
    public function index(SharedNameFilter $filters): Response
    {
        $this->authorize('viewAny', StudentEnrolmentStatus::class);
        $statuses = StudentEnrolmentStatusResource::collection($this->repository->allFilter(['*'], $filters));

        return Inertia::render('students/studentEnrolmentStatuses/Index', [
            'studentEnrolmentStatuses' => $statuses,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function create(): void
    {
        $this->authorize('create', StudentEnrolmentStatus::class);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StudentEnrolmentStatusRequest $request): void
    {
        $this->authorize('create', StudentEnrolmentStatus::class);
        $this->repository->create(StudentEnrolmentStatusDto::fromRequest($request));
    }

    public function show(StudentEnrolmentStatus $studentEnrolmentStatus): void {}

    public function edit(StudentEnrolmentStatus $studentEnrolmentStatus): void {}

    /**
     * @throws AuthorizationException
     */
    public function update(StudentEnrolmentStatusRequest $request, StudentEnrolmentStatus $studentEnrolmentStatus): void
    {
        $this->authorize('update', $studentEnrolmentStatus);
        $this->repository->update($studentEnrolmentStatus, StudentEnrolmentStatusDto::fromRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StudentEnrolmentStatus $studentEnrolmentStatus): void
    {
        $this->authorize('delete', $studentEnrolmentStatus);
        $this->repository->delete($studentEnrolmentStatus);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $studentEnrolmentStatus = $this->repository->findTrashed($id);
        $this->authorize('restore', $studentEnrolmentStatus);
        $this->repository->restore($studentEnrolmentStatus);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(StudentEnrolmentStatus $studentEnrolmentStatus): void
    {
        $this->authorize('forceDelete', $studentEnrolmentStatus);
        $this->repository->delete($studentEnrolmentStatus, true);
    }
}
