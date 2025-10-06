<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Filters\Students\StudentProgramFilter;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Institution\FeeStructureResource;
use App\Http\Resources\Students\StudentProgramResource;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\FeeStructure;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use App\Repositories\Students\interface\IStudentProgramRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function faultyApplications(): Response
    {
        $this->authorize('viewAny', StudentProgram::class);

        // 1️⃣ Get allowed department levels (NC + ABMA Level 3)
        $allowedLevelIds = $this->getAllowedLevelIds();

        // 2️⃣ Fetch IDs for problematic applications
        $noOLevelResultsIds = $this->getNoOLevelResultsIds($allowedLevelIds);
        $oLevelResultsFewerThanFiveIds = $this->getOLevelResultsFewerThanFiveIds($allowedLevelIds);

        // 3️⃣ Query and paginate faulty applications
        $noOLevelResults = StudentProgram::with('student')
            ->whereIn('id', $noOLevelResultsIds)
            ->latest()
            ->paginate(1000);

        $oLevelResultsFewerThanFive = StudentProgram::with('student')
            ->whereIn('id', $oLevelResultsFewerThanFiveIds)
            ->latest()
            ->paginate(1000);

        // 4️⃣ Transform for frontend
        return Inertia::render('students/enrolments/FaultyApplications', [
            'enrolmentWithoutOLevel' => EnrolmentResource::collection($noOLevelResults),
            'enrolmentWithFewerThanFive' => EnrolmentResource::collection($oLevelResultsFewerThanFive),
        ]);
    }

    private function getAllowedLevelIds()
    {
        return DepartmentLevel::whereHas('level', function ($query) {
            $query->whereIn('name', [
                LevelEnum::NC->name(),
                LevelEnum::ABMA_LEVEL_3->name(),
            ]);
        })->pluck('id');
    }

    private function getNoOLevelResultsIds($allowedLevelIds)
    {
        return StudentProgram::select(DB::raw('MAX(id) as id'))
            ->whereHas('student', function (Builder $query) {
                $query->whereDoesntHave('oLevelResults');
            })
            ->whereIn('department_level_id', $allowedLevelIds)
            ->groupBy('student_id')
            ->pluck('id');
    }

    private function getOLevelResultsFewerThanFiveIds($allowedLevelIds)
    {
        return StudentProgram::select(DB::raw('MAX(id) as id'))
            ->whereHas('student', function (Builder $query) {
                $query->has('oLevelResults', '>=', 1)
                    ->has('oLevelResults', '<', 5);
            })
            ->whereIn('department_level_id', $allowedLevelIds)
            ->groupBy('student_id')
            ->pluck('id');
    }
}
