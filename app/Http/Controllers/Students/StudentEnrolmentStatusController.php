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
        $this->authorize('viewSettings');
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
        $this->authorize('createSettings');
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StudentEnrolmentStatusRequest $request): void
    {
        $this->authorize('createSettings');
        $this->repository->create(StudentEnrolmentStatusDto::fromRequest($request));
    }

    public function show(StudentEnrolmentStatus $studentEnrolmentStatus): void {}

    public function edit(StudentEnrolmentStatus $studentEnrolmentStatus): void {}

    /**
     * @throws AuthorizationException
     */
    public function update(StudentEnrolmentStatusRequest $request, StudentEnrolmentStatus $studentEnrolmentStatus): void
    {
        $this->authorize('updateSettings');
        $this->repository->update($studentEnrolmentStatus, StudentEnrolmentStatusDto::fromRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(StudentEnrolmentStatus $studentEnrolmentStatus): void
    {
        $this->authorize('deleteSettings');
        $this->repository->delete($studentEnrolmentStatus);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $status = $this->repository->findTrashed($id);
        $this->authorize('restoreSettings');
        $this->repository->restore($status);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(StudentEnrolmentStatus $studentEnrolmentStatus): void
    {
        $this->authorize('forceDeleteSettings');
        $this->repository->delete($studentEnrolmentStatus, true);
    }
}
