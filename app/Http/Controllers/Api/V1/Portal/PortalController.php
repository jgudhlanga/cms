<?php

namespace App\Http\Controllers\Api\V1\Portal;

use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Students\SponsorResource;
use App\Http\Resources\Students\StudentProgramResource;
use App\Http\Resources\Students\StudentResource;
use App\Repositories\Students\interface\IStudentRepository;
use App\Traits\HttpUtil;
use Illuminate\Auth\AuthenticationException;

class PortalController
{
    use HttpUtil;

    public function __construct(protected IStudentRepository $repository)
    {
    }

    /**
     * @throws AuthenticationException
     */
    public function personal()
    {
        return StudentResource::make($this->getStudent());
    }

    /**
     * @throws AuthenticationException
     */
    public function programs()
    {
        $student = $this->getStudent();
        return StudentProgramResource::collection($student->programs);
    }

    /**
     * @throws AuthenticationException
     */
    public function addresses()
    {
        $student = $this->getStudent();
        return AddressResource::collection($student->addresses);
    }

    /**
     * @throws AuthenticationException
     */
    public function contacts()
    {
        $student = $this->getStudent();
        return ContactResource::collection($student->contacts);
    }

    /**
     * @throws AuthenticationException
     */
    public function sponsors()
    {
        $student = $this->getStudent();
        return SponsorResource::collection($student->sponsors);
    }

    /**
     * @throws AuthenticationException
     */
    private function getStudent()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            throw new AuthenticationException();
        }
        return $user->studentProfile;
    }
}
