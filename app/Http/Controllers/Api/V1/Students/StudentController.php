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
use App\Services\Students\StudentProgrammeDataService;
use App\Traits\HttpUtil;

class StudentController
{
    use HttpUtil;

    public function __construct(
        protected IStudentRepository $repository,
        protected StudentProgrammeDataService $programmeDataService,
    ) {}

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
                'gender',
                'student_type',
                'academic_year',
                'calendar_type',
                'with_trashed',
            ])
        );

        return StudentResource::collection($students);
    }

    public function stats()
    {
        return response()->json(
            $this->repository->statsForIndex(
                request()->only([
                    'search',
                    'name',
                    'department',
                    'level',
                    'course',
                    'mode_of_study',
                    'gender',
                    'student_type',
                    'with_trashed',
                ])
            )
        );
    }

    public function studentEnrolements(Student $student)
    {
        abort_unless(request()->user()?->can('view', $student) ?? false, 403);

        return $this->success($this->programmeDataService->buildProgrammesForStudent($student));
    }

    // ====== STUDENT ===========
    public function personal(Student $student)
    {
        return StudentResource::make($student);
    }

    public function programs(Student $student)
    {
        $student->loadMissing([
            'applications.student.user',
            'applications.student.idType',
            'applications.student.country',
            'applications.student.contacts',
            'applications.student.oLevelResults.academicLevel',
            'applications.student.oLevelResults.subject',
            'applications.student.oLevelResults.grade',
            'applications.modeOfStudy',
            'applications.institutionDepartment.department',
            'applications.departmentLevel.level',
            'applications.departmentLevel.requirement',
            'applications.departmentCourse.course',
            'applications.departmentCourse.requirement',
            'applications.intakePeriod',
            'applications.classList',
            'applications.departmentWorkflowStep.workflowStep',
        ]);

        return EnrolmentResource::collection($student->applications);
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
        $student->loadMissing(['sponsors.sponsorType', 'sponsors.contacts', 'sponsors.addresses']);

        return SponsorResource::collection($student->sponsors);
    }

    public function nextOfKin(Student $student)
    {
        $student->loadMissing(['nextOfKins.relationship', 'nextOfKins.contacts', 'nextOfKins.addresses']);

        return NextOfKinResource::collection($student->nextOfKins);
    }
}
