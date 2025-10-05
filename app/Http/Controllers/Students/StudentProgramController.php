<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Filters\Students\StudentProgramFilter;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Institution\FeeStructureResource;
use App\Http\Resources\Students\StudentProgramResource;
use App\Models\Institution\FeeStructure;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentProgramController extends Controller
{
    public function __construct(
        protected IStudentProgramRepository  $repository,
        protected IDepartmentLevelRepository $departmentLevelRepository,
    )
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function index(StudentProgramFilter $filters): Response
    {
        $this->authorize('viewAny', StudentProgram::class);
        $enrolments = StudentProgramResource::collection($this->repository->allFilter(['*'], $filters));
        return Inertia::render('students/EnrolmentsIndex', [
            'enrolments' => $enrolments,
            'filters' => request()->only(['search', 'trashed']),
            'trashedCount' => $this->repository->allTrashed()->count(),
        ]);
    }


    public function create()
    {
        return Inertia::render('students/enrolments/Create');
    }

    public function paymentVerification()
    {
        $feeType = PaymentHelper::getFeeTypeBySlug(FeeTypeEnum::APPLICATION_FEE->slug());
        $registrationFee = FeeStructure::where('fee_type_id', $feeType->id)->first();
        $registrationFee = FeeStructureResource::make($registrationFee);
        return Inertia::render('students/paymentVerification/PaymentVerification', compact('registrationFee'));
    }

    public function searchProfile()
    {
        [$search] = $this->extractRequestFilters();
        // search users;
        // search students;
        // search ledgers;
    }

    public function store(Request $request)
    {
        //
    }


    public function show(string $id)
    {
        //
    }


    public function edit(string $id)
    {
        //
    }


    public function update(UpdateStudentRequest $request, Student $student): void
    {
        $this->repository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    public function destroy(string $id)
    {
        //
    }

    private function extractRequestFilters(): array
    {
        $search = request()->has('search') ? request('search') : null;

        return [
            $search,
        ];
    }
}
