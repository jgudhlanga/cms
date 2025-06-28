<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\SponsorDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\SponsorRequest;
use App\Models\Students\Sponsor;
use App\Repositories\Students\interface\ISponsorRepository;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function __construct(protected ISponsorRepository $repository)
    {
    }

    /**
     * Store a newly created sponsor.
     */
    public function store(SponsorRequest $request)
    {
        $this->repository->create(
            SponsorDto::fromSponsorRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Update the specified sponsor.
     */
    public function update(SponsorRequest $request, Sponsor $sponsor)
    {
        $this->repository->update(
            $sponsor,
            SponsorDto::fromSponsorRequest($request, $this->getStudent($request))
        );
    }

    /**
     * Soft delete the specified sponsor.
     */
    public function destroy(Sponsor $sponsor)
    {
        $this->repository->delete($sponsor);
    }

    /**
     * Restore a soft-deleted sponsor.
     */
    public function restore(string $id)
    {
        $sponsor = $this->repository->findTrashed($id);
        $this->repository->restore($sponsor);
    }

    /**
     * Permanently delete the specified sponsor.
     */
    public function forceDelete(Sponsor $sponsor)
    {
        $this->repository->delete($sponsor, true);
    }

    /**
     * Retrieve the student profile from the request user.
     */
    private function getStudent(Request $request)
    {
        return $request->user()->studentProfile;
    }
}
