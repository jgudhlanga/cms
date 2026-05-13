<?php

namespace App\Http\Controllers\Api\V1\Students;

use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Shared\AddressResource;
use App\Http\Resources\Shared\ContactResource;
use App\Http\Resources\Shared\NextOfKinResource;
use App\Http\Resources\Students\SponsorResource;
use App\Http\Resources\Students\StudentResource;
use App\Models\Students\Student;
use App\Repositories\Students\interface\IStudentRepository;
use App\Traits\HttpUtil;

class StudentController
{
    use HttpUtil;

    public function __construct(protected IStudentRepository $repository) {}

    public function index()
    {
        $students = $this->repository->paginateForIndex(
            request()->only([
                'search',
                'name',
                'department',
                'level',
                'course',
                'mode_of_study',
                'academic_year',
                'calendar_type',
                'with_trashed',
            ])
        );

        return StudentResource::collection($students);
    }

    // ====== STUDENT ===========
    public function personal(Student $student)
    {
        $student->loadMissing([
            'currentEnrolment.departmentLevelCourse.courseSyllabuses',
        ]);

        return StudentResource::make($student);
    }

    public function programs(Student $student)
    {
        return EnrolmentResource::collection($student->programs);
    }

    public function addresses(Student $student)
    {
        return AddressResource::collection($student->addresses);
    }

    public function contacts(Student $student)
    {
        return ContactResource::collection($student->contacts);
    }

    public function sponsors(Student $student)
    {
        return SponsorResource::collection($student->sponsors);
    }

    public function nextOfKin(Student $student)
    {
        return NextOfKinResource::collection($student->nextOfKins);
    }
}
