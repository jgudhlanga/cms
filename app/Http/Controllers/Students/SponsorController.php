<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\SponsorDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\SponsorRequest;
use App\Models\Students\Sponsor;
use App\Models\Students\Student;
use App\Repositories\Students\interface\ISponsorRepository;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function __construct(protected ISponsorRepository $repository) {}

    /**
     * Store a newly created sponsor.
     */
    public function store(SponsorRequest $request)
    {
        $this->authorize('manageStudentSponsors');
        abort_if($this->getStudent($request) === null, 403);

        $this->repository->create(
            SponsorDto::fromSponsorRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Update the specified sponsor.
     */
    public function update(SponsorRequest $request, Sponsor $sponsor)
    {
        $this->authorizeSponsor($request, $sponsor);
        $this->repository->update(
            $sponsor,
            SponsorDto::fromSponsorRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Soft delete the specified sponsor.
     */
    public function destroy(Request $request, Sponsor $sponsor)
    {
        $this->authorizeSponsor($request, $sponsor);
        $this->repository->delete($sponsor);
    }

    /**
     * Restore a soft-deleted sponsor.
     */
    public function restore(Request $request, string $id)
    {
        $sponsor = $this->repository->findTrashed($id);
        $this->authorizeSponsor($request, $sponsor);
        $this->repository->restore($sponsor);
    }

    /**
     * Permanently delete the specified sponsor.
     */
    public function forceDelete(Request $request, Sponsor $sponsor)
    {
        $this->authorizeSponsor($request, $sponsor);
        $this->repository->delete($sponsor, true);
    }

    /**
     * Sponsors are managed by the student they belong to, or by staff allowed to update that student.
     */
    private function authorizeSponsor(Request $request, Sponsor $sponsor): void
    {
        $this->authorize('manageStudentSponsors');

        $ownStudentId = $this->getStudent($request)?->id;

        if ($ownStudentId !== null && (int) $sponsor->student_id === (int) $ownStudentId) {
            return;
        }

        $student = Student::query()->find($sponsor->student_id);

        abort_unless($student !== null && $request->user()->can('update', $student), 403);
    }

    /**
     * Retrieve the student profile from the request user.
     */
    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }
}
